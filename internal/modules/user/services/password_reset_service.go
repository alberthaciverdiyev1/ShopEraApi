package services

import (
	"strconv"
	"time"

	"shopera/internal/helpers"
	userrepositories "shopera/internal/modules/user/repositories"
)

// PasswordResetService handles password reset flows (phone OTP, e-mail code and
// admin-mediated requests).
//
// NOTE: NOT wired into routes or register yet — code only (per current decision).
type PasswordResetService struct {
	users  *userrepositories.UserRepository
	otps   *userrepositories.OtpRepository
	resets *userrepositories.PasswordResetRepository
	tokens *userrepositories.RefreshTokenRepository
	mailer Mailer
}

func NewPasswordResetService(
	users *userrepositories.UserRepository,
	otps *userrepositories.OtpRepository,
	resets *userrepositories.PasswordResetRepository,
	tokens *userrepositories.RefreshTokenRepository,
	mailer Mailer,
) *PasswordResetService {
	return &PasswordResetService{users: users, otps: otps, resets: resets, tokens: tokens, mailer: mailer}
}

// SendEmailCode e-mails a 4-digit code (same answer whether the address is known).
func (s *PasswordResetService) SendEmailCode(email string) (time.Time, error) {
	code := randomCode()
	deactiveAt := time.Now().Add(10 * time.Minute)

	user, err := s.users.FindByEmail(email)
	if err != nil {
		return time.Time{}, err
	}
	if user == nil {
		// Do not reveal whether the address exists; return the nominal expiry.
		return deactiveAt, nil
	}
	if err := s.otps.Upsert(email, code, deactiveAt); err != nil {
		return time.Time{}, err
	}
	if err := s.mailer.Send(email, "Password reset code", "Your code: "+strconv.Itoa(code)); err != nil {
		return time.Time{}, err
	}
	return deactiveAt, nil
}

// ResetByEmail sets a new password using an e-mailed code.
func (s *PasswordResetService) ResetByEmail(email, otpCode, password string) (int64, error) {
	valid, err := s.otps.FindValid(email, otpCode)
	if err != nil {
		return 0, err
	}
	if valid == nil {
		return 0, nil // invalid/expired
	}
	user, err := s.users.FindByEmail(email)
	if err != nil || user == nil {
		return 0, err
	}
	if err := s.updatePassword(user.ID, password); err != nil {
		return 0, err
	}
	_ = s.otps.DeleteByKey(email)
	_ = s.tokens.RevokeAllForUser(user.ID)
	return user.ID, nil
}

// ResetByPhone sets a new password using a phone OTP.
func (s *PasswordResetService) ResetByPhone(phone, otpCode, password string) (int64, error) {
	valid, err := s.otps.FindValid(phone, otpCode)
	if err != nil {
		return 0, err
	}
	if valid == nil {
		return 0, nil
	}
	user, err := s.users.FindByPhone(phone)
	if err != nil || user == nil {
		return 0, err
	}
	if err := s.updatePassword(user.ID, password); err != nil {
		return 0, err
	}
	_ = s.otps.DeleteByKey(phone)
	_ = s.tokens.RevokeAllForUser(user.ID)
	return user.ID, nil
}

// ChangePassword verifies the current password and sets a new one (tokens rotated).
func (s *PasswordResetService) ChangePassword(userID int64, current, newPassword string) (bool, error) {
	user, err := s.users.FindByID(userID)
	if err != nil || user == nil {
		return false, err
	}
	if !helpers.CheckPassword(user.Password, current) {
		return false, nil
	}
	if err := s.updatePassword(userID, newPassword); err != nil {
		return false, err
	}
	_ = s.tokens.RevokeAllForUser(userID)
	return true, nil
}

// CreateRequest records an admin-mediated reset request (202, address hidden).
func (s *PasswordResetService) CreateRequest(phone string, note *string) error {
	user, err := s.users.FindByPhone(phone)
	if err != nil {
		return err
	}
	if user == nil || !user.IsActive {
		return nil
	}
	return s.resets.FirstOrCreatePending(user.ID, phone, note)
}

// ListRequests returns admin password-reset requests.
func (s *PasswordResetService) ListRequests(status *string, search string, page, perPage int) ([]int64, int64, error) {
	items, total, err := s.resets.List(status, search, perPage, (page-1)*perPage)
	if err != nil {
		return nil, 0, err
	}
	ids := make([]int64, 0, len(items))
	for _, r := range items {
		ids = append(ids, r.ID)
	}
	return ids, total, nil
}

// ResolveRequest sets the user's new password and marks the request resolved.
func (s *PasswordResetService) ResolveRequest(id, resolverID int64, password string) (bool, error) {
	req, err := s.resets.FindPending(id)
	if err != nil || req == nil {
		return false, err
	}
	if err := s.updatePassword(req.UserID, password); err != nil {
		return false, err
	}
	if err := s.resets.Mark(id, "resolved", resolverID); err != nil {
		return false, err
	}
	return true, nil
}

// DismissRequest marks a request dismissed.
func (s *PasswordResetService) DismissRequest(id, resolverID int64) (bool, error) {
	req, err := s.resets.FindPending(id)
	if err != nil || req == nil {
		return false, err
	}
	if err := s.resets.Mark(id, "dismissed", resolverID); err != nil {
		return false, err
	}
	return true, nil
}

func (s *PasswordResetService) updatePassword(userID int64, plain string) error {
	hashed, err := helpers.HashPassword(plain)
	if err != nil {
		return err
	}
	return s.users.UpdateFields(userID, map[string]any{"password": hashed})
}
