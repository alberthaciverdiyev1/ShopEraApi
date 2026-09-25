package services

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	balancerepositories "shopera/internal/modules/balance/repositories"
	rolepermissionrepositories "shopera/internal/modules/rolepermission/repositories"
	settingservices "shopera/internal/modules/setting/services"
	usermodels "shopera/internal/modules/user/models"
	userrepositories "shopera/internal/modules/user/repositories"
	userresponses "shopera/internal/modules/user/responses"
)

// AdminUserService holds user-management logic (block, wholesale, details, delete).
type AdminUserService struct {
	users    *userrepositories.UserRepository
	tokens   *userrepositories.RefreshTokenRepository
	roles    *rolepermissionrepositories.RoleRepository
	balances *balancerepositories.BalanceRepository
	settings *settingservices.SettingService
}

func NewAdminUserService(
	users *userrepositories.UserRepository,
	tokens *userrepositories.RefreshTokenRepository,
	roles *rolepermissionrepositories.RoleRepository,
	balances *balancerepositories.BalanceRepository,
	settings *settingservices.SettingService,
) *AdminUserService {
	return &AdminUserService{users: users, tokens: tokens, roles: roles, balances: balances, settings: settings}
}

// Block activates/deactivates a user; blocking revokes their sessions.
func (s *AdminUserService) Block(actorID, userID int64, block bool) (map[string]any, error) {
	user, err := s.users.FindByID(userID)
	if err != nil {
		return nil, err
	}
	if user == nil {
		return nil, helpers.NewAppError(404, "User not found")
	}
	if user.ID == actorID {
		return nil, helpers.NewAppError(403, "You cannot block your own account")
	}
	if user.IsActive != block {
		if block {
			return nil, helpers.NewAppError(403, "User is already blocked.")
		}
		return nil, helpers.NewAppError(403, "User is already unblocked.")
	}

	if err := s.users.UpdateFields(user.ID, map[string]any{"is_active": !block}); err != nil {
		return nil, err
	}
	if block {
		_ = s.tokens.RevokeAllForUser(user.ID)
	}
	user.IsActive = !block
	return s.resource(user)
}

// ChangeWholesalerStatus toggles a user's wholesaler flag.
func (s *AdminUserService) ChangeWholesalerStatus(userID int64, isWholesaler bool) (map[string]any, error) {
	user, err := s.users.FindByID(userID)
	if err != nil {
		return nil, err
	}
	if user == nil {
		return nil, helpers.NewAppError(404, "User not found")
	}
	if err := s.users.UpdateFields(user.ID, map[string]any{"is_wholesaler": isWholesaler}); err != nil {
		return nil, err
	}
	user.IsWholesaler = isWholesaler
	return s.resource(user)
}

// Details returns a user with their roles and each role's permissions.
func (s *AdminUserService) Details(actorID int64, targetID *int64) (gin.H, error) {
	id := actorID
	if targetID != nil {
		id = *targetID
	}

	user, err := s.users.FindByID(id)
	if err != nil {
		return nil, err
	}
	if user == nil {
		return nil, helpers.NewAppError(404, "User not found")
	}

	roles, err := s.roles.UserRoles(id)
	if err != nil {
		return nil, err
	}
	roleNames := make([]string, 0, len(roles))
	roleList := make([]gin.H, 0, len(roles))
	for _, role := range roles {
		roleNames = append(roleNames, role.Name)
		perms, err := s.roles.RolePermissions(role.ID)
		if err != nil {
			return nil, err
		}
		permNames := make([]string, 0, len(perms))
		for _, p := range perms {
			permNames = append(permNames, p.Name)
		}
		roleList = append(roleList, gin.H{"name": role.Name, "permissions": permNames})
	}

	resource, err := s.resourceWithRoles(user, roleNames)
	if err != nil {
		return nil, err
	}
	return gin.H{"user": resource, "roles": roleList}, nil
}

// Delete permanently removes another user.
func (s *AdminUserService) Delete(actorID, targetID int64) error {
	if targetID == actorID {
		return helpers.NewAppError(403, "You cannot delete your own account")
	}
	user, err := s.users.FindByID(targetID)
	if err != nil {
		return err
	}
	if user == nil {
		return helpers.NewAppError(404, "User not found")
	}
	return s.users.ForceDelete(targetID)
}

// DeleteMyAccount soft-deletes the actor's account and revokes their sessions.
func (s *AdminUserService) DeleteMyAccount(actorID int64) error {
	user, err := s.users.FindByID(actorID)
	if err != nil {
		return err
	}
	if user == nil {
		return helpers.NewAppError(403, "User not authenticated")
	}
	_ = s.tokens.RevokeAllForUser(actorID)
	return s.users.SoftDelete(actorID)
}

// resource builds the admin representation of a single user (roles loaded).
func (s *AdminUserService) resource(user *usermodels.User) (map[string]any, error) {
	roles, err := s.roles.UserRoles(user.ID)
	if err != nil {
		return nil, err
	}
	names := make([]string, 0, len(roles))
	for _, role := range roles {
		names = append(names, role.Name)
	}
	return s.resourceWithRoles(user, names)
}

func (s *AdminUserService) resourceWithRoles(user *usermodels.User, roleNames []string) (map[string]any, error) {
	balance, err := s.balances.Sum(user.ID)
	if err != nil {
		return nil, err
	}
	return userresponses.Resource(user, balance, s.settings.MinimalPurchasePrice(), roleNames), nil
}
