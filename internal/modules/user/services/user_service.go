// Package services holds User module services (OTP, password reset, profile).
package services

import (
	"strings"

	"shopera/internal/helpers"
	userhelpers "shopera/internal/modules/user/helpers"
	usermodels "shopera/internal/modules/user/models"
	userrepositories "shopera/internal/modules/user/repositories"
	userrequests "shopera/internal/modules/user/requests"
	userresponses "shopera/internal/modules/user/responses"
)

// UserService holds profile-change logic (name, surname, e-mail, phone).
type UserService struct {
	users *userrepositories.UserRepository
	otps  *userrepositories.OtpRepository
}

func NewUserService(users *userrepositories.UserRepository, otps *userrepositories.OtpRepository) *UserService {
	return &UserService{users: users, otps: otps}
}

// ChangeEmail sets the e-mail of the actor (or of user_id when provided).
func (s *UserService) ChangeEmail(actorID int64, in userrequests.ChangeEmailRequest) (map[string]any, error) {
	user, err := s.target(actorID, in.UserID)
	if err != nil {
		return nil, err
	}

	email := strings.ToLower(strings.TrimSpace(in.Email))
	if user.Email == nil || *user.Email != email {
		taken, err := s.users.ExistsByEmail(email)
		if err != nil {
			return nil, err
		}
		if taken {
			return nil, helpers.NewAppError(422, "This email address is already in use.")
		}
	}

	if err := s.users.UpdateFields(user.ID, map[string]any{"email": email}); err != nil {
		return nil, err
	}
	user.Email = &email
	return userresponses.Payload(user, true), nil
}

// ChangeName lower-cases and sets the name of the actor (or of user_id).
func (s *UserService) ChangeName(actorID int64, in userrequests.ChangeNameRequest) (map[string]any, error) {
	user, err := s.target(actorID, in.UserID)
	if err != nil {
		return nil, err
	}

	name := strings.ToLower(in.Name)
	if err := s.users.UpdateFields(user.ID, map[string]any{"name": name}); err != nil {
		return nil, err
	}
	user.Name = &name
	return userresponses.Payload(user, true), nil
}

// ChangeSurname lower-cases and sets the surname of the actor (or of user_id).
func (s *UserService) ChangeSurname(actorID int64, in userrequests.ChangeSurnameRequest) (map[string]any, error) {
	user, err := s.target(actorID, in.UserID)
	if err != nil {
		return nil, err
	}

	surname := strings.ToLower(in.Surname)
	if err := s.users.UpdateFields(user.ID, map[string]any{"surname": surname}); err != nil {
		return nil, err
	}
	user.Surname = &surname
	return userresponses.Payload(user, true), nil
}

// ChangePhone verifies a phone OTP and moves the actor to the new number.
func (s *UserService) ChangePhone(actorID int64, in userrequests.ChangePhoneRequest) (map[string]any, error) {
	normalized := userhelpers.NormalizePhone(in.Phone)
	if normalized == "" {
		return nil, helpers.NewAppError(422, "The phone field must contain a valid phone number.")
	}

	taken, err := s.users.PhoneTakenByOther(normalized, actorID)
	if err != nil {
		return nil, err
	}
	if taken {
		return nil, helpers.NewAppError(422, "This phone number is already in use.")
	}

	otp, err := s.otps.FindValid(normalized, in.OtpCode)
	if err != nil {
		return nil, err
	}
	if otp == nil {
		return nil, helpers.NewAppError(403, "OTP code is invalid or expired.")
	}

	if err := s.users.UpdateFields(actorID, map[string]any{"phone": normalized}); err != nil {
		return nil, err
	}
	_ = s.otps.DeleteByKey(normalized)

	user, err := s.users.FindByID(actorID)
	if err != nil {
		return nil, err
	}
	if user == nil {
		return nil, helpers.NewAppError(404, "User not found or not authenticated.")
	}
	return userresponses.Payload(user, true), nil
}

// target resolves the user to change: user_id when given, otherwise the actor.
func (s *UserService) target(actorID int64, userID *int64) (*usermodels.User, error) {
	id := actorID
	if userID != nil {
		id = *userID
	}
	user, err := s.users.FindByID(id)
	if err != nil {
		return nil, err
	}
	if user == nil {
		return nil, helpers.NewAppError(404, "User not found or not authenticated.")
	}
	return user, nil
}
