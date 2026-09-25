// Package repositories holds the data access for roles and permissions (spatie).
package repositories

import (
	"errors"

	"gorm.io/gorm"

	"shopera/internal/modules/rolepermission/models"
)

// RoleRepository is the data access for roles/permissions.
type RoleRepository struct {
	db *gorm.DB
}

func NewRoleRepository(db *gorm.DB) *RoleRepository { return &RoleRepository{db: db} }

// Roles returns every role.
func (r *RoleRepository) Roles() ([]models.Role, error) {
	var items []models.Role
	err := r.db.Order("id asc").Find(&items).Error
	return items, err
}

// RolePermissions returns a role's permissions.
func (r *RoleRepository) RolePermissions(roleID int64) ([]models.Permission, error) {
	var items []models.Permission
	err := r.db.
		Joins("JOIN role_has_permissions rhp ON rhp.permission_id = permissions.id").
		Where("rhp.role_id = ?", roleID).
		Order("permissions.id asc").
		Find(&items).Error
	return items, err
}

// FindRole returns a role by id.
func (r *RoleRepository) FindRole(id int64) (*models.Role, error) {
	var role models.Role
	err := r.db.First(&role, id).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &role, nil
}

// FindRoleByName returns a role by name (guard: sanctum).
func (r *RoleRepository) FindRoleByName(name string) (*models.Role, error) {
	var role models.Role
	err := r.db.Where("name = ? AND guard_name = ?", name, models.Guard).First(&role).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &role, nil
}

// CreateRole inserts a role.
func (r *RoleRepository) CreateRole(name string) (*models.Role, error) {
	role := &models.Role{Name: name, GuardName: models.Guard}
	if err := r.db.Create(role).Error; err != nil {
		return nil, err
	}
	return role, nil
}

// UpdateRole renames a role.
func (r *RoleRepository) UpdateRole(id int64, name string) (*models.Role, error) {
	if err := r.db.Model(&models.Role{}).Where("id = ?", id).Update("name", name).Error; err != nil {
		return nil, err
	}
	return r.FindRole(id)
}

// DeleteRole removes a role and its pivots.
func (r *RoleRepository) DeleteRole(id int64) error {
	return r.db.Transaction(func(tx *gorm.DB) error {
		if err := tx.Exec("DELETE FROM role_has_permissions WHERE role_id = ?", id).Error; err != nil {
			return err
		}
		if err := tx.Exec("DELETE FROM model_has_roles WHERE role_id = ?", id).Error; err != nil {
			return err
		}
		return tx.Delete(&models.Role{}, id).Error
	})
}

// GivePermissionToRole assigns a permission (creating it if needed).
func (r *RoleRepository) GivePermissionToRole(roleID int64, permissionName string) error {
	permission, err := r.firstOrCreatePermission(permissionName)
	if err != nil {
		return err
	}
	return r.db.Exec(
		`INSERT INTO role_has_permissions (permission_id, role_id) VALUES (?, ?)
		 ON CONFLICT (permission_id, role_id) DO NOTHING`,
		permission.ID, roleID,
	).Error
}

// RevokePermissionFromRole removes a permission from a role.
func (r *RoleRepository) RevokePermissionFromRole(roleID int64, permissionName string) error {
	return r.db.Exec(
		`DELETE FROM role_has_permissions
		 WHERE role_id = ? AND permission_id IN (SELECT id FROM permissions WHERE name = ? AND guard_name = ?)`,
		roleID, permissionName, models.Guard,
	).Error
}

// AssignRoleToUser assigns a role to a user (idempotent).
func (r *RoleRepository) AssignRoleToUser(userID int64, roleName string) error {
	role, err := r.FindRoleByName(roleName)
	if err != nil || role == nil {
		return err
	}
	return r.db.Exec(
		`INSERT INTO model_has_roles (role_id, model_type, model_id) VALUES (?, ?, ?)
		 ON CONFLICT (role_id, model_type, model_id) DO NOTHING`,
		role.ID, userMorph, userID,
	).Error
}

// RevokeRoleFromUser removes a role from a user.
func (r *RoleRepository) RevokeRoleFromUser(userID int64, roleName string) error {
	return r.db.Exec(
		`DELETE FROM model_has_roles
		 WHERE model_type = ? AND model_id = ?
		   AND role_id IN (SELECT id FROM roles WHERE name = ? AND guard_name = ?)`,
		userMorph, userID, roleName, models.Guard,
	).Error
}

// UserRoles returns the user's roles.
func (r *RoleRepository) UserRoles(userID int64) ([]models.Role, error) {
	var items []models.Role
	err := r.db.
		Joins("JOIN model_has_roles mhr ON mhr.role_id = roles.id").
		Where("mhr.model_type = ? AND mhr.model_id = ?", userMorph, userID).
		Order("roles.id asc").
		Find(&items).Error
	return items, err
}

// Permissions returns every permission.
func (r *RoleRepository) Permissions() ([]models.Permission, error) {
	var items []models.Permission
	err := r.db.Order("id asc").Find(&items).Error
	return items, err
}

// FindPermission returns a permission by id.
func (r *RoleRepository) FindPermission(id int64) (*models.Permission, error) {
	var p models.Permission
	err := r.db.First(&p, id).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &p, nil
}

// CreatePermission inserts a permission (idempotent).
func (r *RoleRepository) CreatePermission(name string) (*models.Permission, error) {
	return r.firstOrCreatePermission(name)
}

// UpdatePermission renames a permission.
func (r *RoleRepository) UpdatePermission(id int64, name string) (*models.Permission, error) {
	if err := r.db.Model(&models.Permission{}).Where("id = ?", id).Update("name", name).Error; err != nil {
		return nil, err
	}
	return r.FindPermission(id)
}

// DeletePermission removes a permission and its pivots.
func (r *RoleRepository) DeletePermission(id int64) error {
	return r.db.Transaction(func(tx *gorm.DB) error {
		if err := tx.Exec("DELETE FROM role_has_permissions WHERE permission_id = ?", id).Error; err != nil {
			return err
		}
		if err := tx.Exec("DELETE FROM model_has_permissions WHERE permission_id = ?", id).Error; err != nil {
			return err
		}
		return tx.Delete(&models.Permission{}, id).Error
	})
}

func (r *RoleRepository) firstOrCreatePermission(name string) (*models.Permission, error) {
	var p models.Permission
	err := r.db.Where("name = ? AND guard_name = ?", name, models.Guard).First(&p).Error
	if err == nil {
		return &p, nil
	}
	if !errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, err
	}
	p = models.Permission{Name: name, GuardName: models.Guard}
	if err := r.db.Create(&p).Error; err != nil {
		return nil, err
	}
	return &p, nil
}

// userMorph is the Laravel class name stored in the pivot columns — DO NOT CHANGE.
const userMorph = `Modules\User\Http\Entities\User`
