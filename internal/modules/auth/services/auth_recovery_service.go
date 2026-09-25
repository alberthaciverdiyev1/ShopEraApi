package services

import (
	"strconv"
	"strings"
	"time"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	authresponses "shopera/internal/modules/auth/responses"
	userhelpers "shopera/internal/modules/user/helpers"
	usermodels "shopera/internal/modules/user/models"
	userrepositories "shopera/internal/modules/user/repositories"
	userservices "shopera/internal/modules/user/services"
)

// AuthRecoveryService covers OTP verification and password recovery flows
// (phone OTP and e-mail code). It reuses the OTP / password-reset services from
// the user module so the logic lives in one place.
type AuthRecoveryService struct {
	otp     *userservices.OtpService
	reset   *userservices.PasswordResetService
	users   *userrepositories.UserRepository
	auth    *AuthService
	appName string
}

func NewAuthRecoveryService(
	otp *userservices.OtpService,
	reset *userservices.PasswordResetService,
	users *userrepositories.UserRepository,
	auth *AuthService,
	appName string,
) *AuthRecoveryService {
	return &AuthRecoveryService{otp: otp, reset: reset, users: users, auth: auth, appName: appName}
}

// SendOtp issues a phone OTP and returns its expiry.
func (s *AuthRecoveryService) SendOtp(phone string) (time.Time, error) {
	normalized := userhelpers.NormalizePhone(phone)
	if normalized == "" {
		return time.Time{}, helpers.NewAppError(422, "The phone field must contain a valid phone number.")
	}
	return s.otp.SendOtp(normalized, func(code int) string {
		return s.appName + " OTP code: " + strconv.Itoa(code)
	})
}

// CheckOtp verifies a phone OTP. For an unregistered phone (new sign-up) any
// code passes, matching the published app behaviour.
func (s *AuthRecoveryService) CheckOtp(phone, code string) error {
	normalized := userhelpers.NormalizePhone(phone)
	if normalized == "" {
		return helpers.NewAppError(422, "The phone field must contain a valid phone number.")
	}

	exists, err := s.users.ExistsByPhone(normalized)
	if err != nil {
		return err
	}
	if !exists {
		return nil
	}

	valid, err := s.otp.CheckOtp(normalized, code)
	if err != nil {
		return err
	}
	if !valid {
		return helpers.NewAppError(404, "OTP not found or invalid.")
	}
	return nil
}

// ResetPasswordByPhone sets a new password from a phone OTP and signs the user in.
func (s *AuthRecoveryService) ResetPasswordByPhone(phone, code, password string) (*authresponses.Result, error) {
	normalized := userhelpers.NormalizePhone(phone)
	if normalized == "" {
		return nil, helpers.NewAppError(422, "The phone field must contain a valid phone number.")
	}

	userID, err := s.reset.ResetByPhone(normalized, code, password)
	if err != nil {
		return nil, err
	}
	if userID == 0 {
		return nil, helpers.NewAppError(403, "OTP code is invalid or expired.")
	}
	return s.auth.TokensForUser(userID)
}

// SendPasswordResetEmail e-mails a reset code. The answer does not reveal
// whether the address is registered.
func (s *AuthRecoveryService) SendPasswordResetEmail(email string) error {
	email = strings.ToLower(strings.TrimSpace(email))
	if _, err := s.reset.SendEmailCode(email); err != nil {
		return helpers.NewAppError(500, "The message could not be sent. Please try again later.")
	}
	return nil
}

// ResetPasswordByEmail sets a new password from an e-mailed code and signs the user in.
func (s *AuthRecoveryService) ResetPasswordByEmail(email, code, password string) (*authresponses.Result, error) {
	email = strings.ToLower(strings.TrimSpace(email))
	userID, err := s.reset.ResetByEmail(email, code, password)
	if err != nil {
		return nil, err
	}
	if userID == 0 {
		return nil, helpers.NewAppError(403, "OTP code is invalid or expired.")
	}
	return s.auth.TokensForUser(userID)
}

// ChangePassword verifies the current password and returns a fresh token pair.
func (s *AuthRecoveryService) ChangePassword(userID int64, current, newPassword string) (*authresponses.Result, error) {
	ok, err := s.reset.ChangePassword(userID, current, newPassword)
	if err != nil {
		return nil, err
	}
	if !ok {
		return nil, helpers.NewAppError(403, "Current password is incorrect.")
	}
	return s.auth.TokensForUser(userID)
}

// AdminChangePassword sets another user's password and revokes their sessions.
func (s *AuthRecoveryService) AdminChangePassword(userID int64, newPassword string) error {
	ok, err := s.reset.AdminChangePassword(userID, newPassword)
	if err != nil {
		return err
	}
	if !ok {
		return helpers.NewAppError(404, "User not found.")
	}
	return nil
}

// CreateResetRequest records an admin-mediated reset request (address hidden).
func (s *AuthRecoveryService) CreateResetRequest(phone string, note *string) error {
	normalized := userhelpers.NormalizePhone(phone)
	if normalized == "" {
		return helpers.NewAppError(422, "The phone field must contain a valid phone number.")
	}
	return s.reset.CreateRequest(normalized, note)
}

// ListResetRequests returns a paginated admin list of reset requests with user info.
func (s *AuthRecoveryService) ListResetRequests(status *string, search string, page, perPage int) (gin.H, error) {
	items, total, err := s.reset.ListRequests(status, search, page, perPage)
	if err != nil {
		return nil, err
	}

	ids := make([]int64, 0, len(items))
	for _, r := range items {
		ids = append(ids, r.UserID)
	}
	users, err := s.users.FindByIDs(ids)
	if err != nil {
		return nil, err
	}
	byID := make(map[int64]*usermodels.User, len(users))
	for i := range users {
		byID[users[i].ID] = &users[i]
	}

	data := make([]map[string]any, 0, len(items))
	for _, r := range items {
		data = append(data, authresponses.ResetRequestPayload(r, byID[r.UserID]))
	}

	q := helpers.Query{Page: page, PerPage: perPage}
	return gin.H{"data": data, "meta": q.Meta(total)}, nil
}

// ResolveResetRequest sets the user's new password and marks the request resolved.
func (s *AuthRecoveryService) ResolveResetRequest(id, resolverID int64, password string) error {
	ok, err := s.reset.ResolveRequest(id, resolverID, password)
	if err != nil {
		return err
	}
	if !ok {
		return helpers.NewAppError(404, "Password reset request not found.")
	}
	return nil
}

// DismissResetRequest marks the request dismissed.
func (s *AuthRecoveryService) DismissResetRequest(id, resolverID int64) error {
	ok, err := s.reset.DismissRequest(id, resolverID)
	if err != nil {
		return err
	}
	if !ok {
		return helpers.NewAppError(404, "Password reset request not found.")
	}
	return nil
}
