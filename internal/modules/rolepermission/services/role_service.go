// Package services holds RoleAndPermissions business logic.
package services

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	"shopera/internal/modules/rolepermission/models"
	rolerepositories "shopera/internal/modules/rolepermission/repositories"
	roleresponses "shopera/internal/modules/rolepermission/responses"
)

// RoleService holds the role business logic.
type RoleService struct {
	repo *rolerepositories.RoleRepository
}

func NewRoleService(repo *rolerepositories.RoleRepository) *RoleService {
	return &RoleService{repo: repo}
}

// List returns roles, optionally with their permissions.
func (s *RoleService) List(withPermissions bool) ([]gin.H, error) {
	roles, err := s.repo.Roles()
	if err != nil {
		return nil, err
	}
	out := make([]gin.H, 0, len(roles))
	for _, role := range roles {
		var perms []models.Permission
		if withPermissions {
			if perms, err = s.repo.RolePermissions(role.ID); err != nil {
				return nil, err
			}
		}
		out = append(out, roleresponses.RoleJSON(role, perms))
	}
	return out, nil
}

// Add creates a role.
func (s *RoleService) Add(name string) (*models.Role, error) {
	return s.repo.CreateRole(name)
}

// Details returns a role with its permissions.
func (s *RoleService) Details(id int64) (gin.H, error) {
	role, err := s.repo.FindRole(id)
	if err != nil || role == nil {
		return nil, err
	}
	perms, err := s.repo.RolePermissions(role.ID)
	if err != nil {
		return nil, err
	}
	return roleresponses.RoleJSON(*role, perms), nil
}

// Update renames a role.
func (s *RoleService) Update(id int64, name string) (*models.Role, error) {
	return s.repo.UpdateRole(id, name)
}

// Delete removes a role.
func (s *RoleService) Delete(id int64) error {
	role, err := s.repo.FindRole(id)
	if err != nil {
		return err
	}
	if role == nil {
		return helpers.NewAppError(404, "Role not found.")
	}
	return s.repo.DeleteRole(id)
}

// GivePermission assigns a permission to a role.
func (s *RoleService) GivePermission(roleID int64, permission string) ([]gin.H, error) {
	role, err := s.repo.FindRole(roleID)
	if err != nil || role == nil {
		return nil, helpers.NewAppError(404, "Role not found.")
	}
	if err := s.repo.GivePermissionToRole(roleID, permission); err != nil {
		return nil, err
	}
	return s.permissionList(roleID)
}

// RevokePermission removes a permission from a role.
func (s *RoleService) RevokePermission(roleID int64, permission string) ([]gin.H, error) {
	role, err := s.repo.FindRole(roleID)
	if err != nil || role == nil {
		return nil, helpers.NewAppError(404, "Role not found.")
	}
	if err := s.repo.RevokePermissionFromRole(roleID, permission); err != nil {
		return nil, err
	}
	return s.permissionList(roleID)
}

// AssignToUser assigns a role to a user.
func (s *RoleService) AssignToUser(userID int64, roleName string) ([]gin.H, error) {
	if err := s.repo.AssignRoleToUser(userID, roleName); err != nil {
		return nil, err
	}
	return s.userRoles(userID)
}

// RevokeFromUser removes a role from a user.
func (s *RoleService) RevokeFromUser(userID int64, roleName string) ([]gin.H, error) {
	if err := s.repo.RevokeRoleFromUser(userID, roleName); err != nil {
		return nil, err
	}
	return s.userRoles(userID)
}

func (s *RoleService) permissionList(roleID int64) ([]gin.H, error) {
	perms, err := s.repo.RolePermissions(roleID)
	if err != nil {
		return nil, err
	}
	return roleresponses.PermissionCollection(perms), nil
}

func (s *RoleService) userRoles(userID int64) ([]gin.H, error) {
	roles, err := s.repo.UserRoles(userID)
	if err != nil {
		return nil, err
	}
	out := make([]gin.H, 0, len(roles))
	for _, role := range roles {
		out = append(out, roleresponses.RoleJSON(role, nil))
	}
	return out, nil
}
