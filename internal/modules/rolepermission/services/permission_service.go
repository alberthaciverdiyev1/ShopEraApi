package services

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	rolerepositories "shopera/internal/modules/rolepermission/repositories"
	roleresponses "shopera/internal/modules/rolepermission/responses"
)

// PermissionService holds the permission business logic.
type PermissionService struct {
	repo *rolerepositories.RoleRepository
}

func NewPermissionService(repo *rolerepositories.RoleRepository) *PermissionService {
	return &PermissionService{repo: repo}
}

// List returns every permission.
func (s *PermissionService) List() ([]gin.H, error) {
	items, err := s.repo.Permissions()
	if err != nil {
		return nil, err
	}
	return roleresponses.PermissionCollection(items), nil
}

// Create inserts a permission (idempotent).
func (s *PermissionService) Create(name string) (gin.H, error) {
	p, err := s.repo.CreatePermission(name)
	if err != nil {
		return nil, err
	}
	return roleresponses.PermissionJSON(*p), nil
}

// Get returns a permission.
func (s *PermissionService) Get(id int64) (gin.H, error) {
	p, err := s.repo.FindPermission(id)
	if err != nil || p == nil {
		return nil, err
	}
	return roleresponses.PermissionJSON(*p), nil
}

// Update renames a permission.
func (s *PermissionService) Update(id int64, name string) (gin.H, error) {
	p, err := s.repo.UpdatePermission(id, name)
	if err != nil || p == nil {
		return nil, err
	}
	return roleresponses.PermissionJSON(*p), nil
}

// Delete removes a permission.
func (s *PermissionService) Delete(id int64) error {
	p, err := s.repo.FindPermission(id)
	if err != nil {
		return err
	}
	if p == nil {
		return helpers.NewAppError(404, "Permission not found.")
	}
	return s.repo.DeletePermission(id)
}
