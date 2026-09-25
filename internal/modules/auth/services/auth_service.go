// Package services holds Auth module business logic.
package services

import (
	"errors"
	"strings"
	"time"

	"shopera/internal/config"
	"shopera/internal/helpers"
	authrequests "shopera/internal/modules/auth/requests"
	authresponses "shopera/internal/modules/auth/responses"
	rolepermissionrepositories "shopera/internal/modules/rolepermission/repositories"
	userhelpers "shopera/internal/modules/user/helpers"
	usermodels "shopera/internal/modules/user/models"
	userrepositories "shopera/internal/modules/user/repositories"
	userresponses "shopera/internal/modules/user/responses"
)

// AuthService holds the auth business logic.
type AuthService struct {
	users         *userrepositories.UserRepository
	roles         *rolepermissionrepositories.RoleRepository
	refreshtokens *userrepositories.RefreshTokenRepository
	cfg           *config.Config
}

func NewAuthService(users *userrepositories.UserRepository, roles *rolepermissionrepositories.RoleRepository, refreshtokens *userrepositories.RefreshTokenRepository, cfg *config.Config) *AuthService {
	return &AuthService{users: users, roles: roles, refreshtokens: refreshtokens, cfg: cfg}
}

// Register creates a user and returns a token.
func (s *AuthService) Register(in authrequests.RegisterRequest) (*authresponses.Result, error) {
	normalized := userhelpers.NormalizePhone(in.Phone)
	if normalized == "" {
		return nil, helpers.NewAppError(422, "The phone field must contain a valid phone number.")
	}

	exists, err := s.users.ExistsByPhone(normalized)
	if err != nil {
		return nil, err
	}
	if exists {
		return nil, helpers.NewAppError(422, "This phone number is already in use.")
	}

	if in.Email != nil && *in.Email != "" {
		taken, err := s.users.ExistsByEmail(*in.Email)
		if err != nil {
			return nil, err
		}
		if taken {
			return nil, helpers.NewAppError(422, "This email address is already in use.")
		}
	}

	hashed, err := helpers.HashPassword(in.Password)
	if err != nil {
		return nil, err
	}

	now := time.Now()
	newUser := &usermodels.User{
		Name:            lowerPtr(in.Name, "user"),
		Surname:         lowerPtrPtr(in.Surname),
		Phone:           normalized,
		Email:           in.Email,
		Password:        hashed,
		IsActive:        true,
		EmailVerifiedAt: &now,
	}
	if err := s.users.Create(newUser); err != nil {
		if errors.Is(err, userrepositories.ErrDuplicate) {
			return nil, helpers.NewAppError(422, "This record is already in use.")
		}
		return nil, err
	}

	_ = s.roles.AssignRoleToUser(newUser.ID, "user")

	token, err := helpers.GenerateToken(newUser.ID, s.cfg.JWT.Secret, s.cfg.JWT.TTL)
	if err != nil {
		return nil, err
	}
	refresh, err := s.refreshtokens.Issue(newUser.ID, s.cfg.JWT.RefreshTTL)
	if err != nil {
		return nil, err
	}
	return &authresponses.Result{Token: token, RefreshToken: refresh, User: userresponses.Payload(newUser, false)}, nil
}

// Login verifies credentials and returns a token.
func (s *AuthService) Login(in authrequests.LoginRequest) (*authresponses.Result, error) {
	found, err := s.users.FindByPhone(in.Phone)
	if err != nil {
		return nil, err
	}
	if found == nil || !helpers.CheckPassword(found.Password, in.Password) {
		return nil, helpers.NewAppError(403, "Phone or password is incorrect.")
	}
	if !found.IsActive {
		return nil, helpers.NewAppError(403, "User is blocked")
	}

	token, err := helpers.GenerateToken(found.ID, s.cfg.JWT.Secret, s.cfg.JWT.TTL)
	if err != nil {
		return nil, err
	}
	refresh, err := s.refreshtokens.Issue(found.ID, s.cfg.JWT.RefreshTTL)
	if err != nil {
		return nil, err
	}
	return &authresponses.Result{Token: token, RefreshToken: refresh, User: userresponses.Payload(found, true)}, nil
}

func lowerPtr(value, fallback string) *string {
	if value == "" {
		value = fallback
	}
	lowered := strings.ToLower(value)
	return &lowered
}

func lowerPtrPtr(value *string) *string {
	if value == nil || *value == "" {
		return nil
	}
	lowered := strings.ToLower(*value)
	return &lowered
}

// Logout revokes the refresh token (access tokens are short-lived JWTs).
func (s *AuthService) Logout(refreshToken string) error {
	if refreshToken == "" {
		return nil
	}
	return s.refreshtokens.Revoke(refreshToken)
}

// Refresh validates a refresh token, rotates it and returns a new token pair.
func (s *AuthService) Refresh(refreshToken string) (*authresponses.Result, error) {
	userID, err := s.refreshtokens.Resolve(refreshToken)
	if err != nil {
		return nil, err
	}
	if userID == 0 {
		return nil, helpers.NewAppError(401, "Invalid refresh token.")
	}
	user, err := s.users.FindByID(userID)
	if err != nil || user == nil {
		return nil, helpers.NewAppError(401, "Invalid refresh token.")
	}

	_ = s.refreshtokens.Revoke(refreshToken) // rotate
	access, err := helpers.GenerateToken(userID, s.cfg.JWT.Secret, s.cfg.JWT.TTL)
	if err != nil {
		return nil, err
	}
	refresh, err := s.refreshtokens.Issue(userID, s.cfg.JWT.RefreshTTL)
	if err != nil {
		return nil, err
	}
	return &authresponses.Result{Token: access, RefreshToken: refresh, User: userresponses.Payload(user, true)}, nil
}
