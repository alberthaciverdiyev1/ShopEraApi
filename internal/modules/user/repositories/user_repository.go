// Package repositories holds the data access, one repository per model.
package repositories

import (
	"errors"
	"strings"

	"gorm.io/gorm"

	rolepermissionmodels "shopera/internal/modules/rolepermission/models"
	userhelpers "shopera/internal/modules/user/helpers"
	"shopera/internal/modules/user/models"
)

// ErrDuplicate is returned when a unique constraint is violated.
var ErrDuplicate = errors.New("duplicate record")

// UserRepository is the data access for the User model.
type UserRepository struct {
	db *gorm.DB
}

func NewUserRepository(db *gorm.DB) *UserRepository { return &UserRepository{db: db} }

// ExistsByPhone reports whether a user with the normalized phone exists.
func (r *UserRepository) ExistsByPhone(phoneNumber string) (bool, error) {
	var count int64
	err := r.db.Model(&models.User{}).Where(userhelpers.PhoneMatchSQL, userhelpers.NormalizePhone(phoneNumber)).Count(&count).Error
	return count > 0, err
}

// ExistsByEmail reports whether a user with the email exists (case-insensitive).
func (r *UserRepository) ExistsByEmail(email string) (bool, error) {
	var count int64
	err := r.db.Model(&models.User{}).Where("lower(email) = ?", strings.ToLower(email)).Count(&count).Error
	return count > 0, err
}

// FindByPhone returns a user by normalized phone.
func (r *UserRepository) FindByPhone(phoneNumber string) (*models.User, error) {
	var u models.User
	err := r.db.Where(userhelpers.PhoneMatchSQL, userhelpers.NormalizePhone(phoneNumber)).First(&u).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &u, nil
}

// FindByID returns a user by id.
func (r *UserRepository) FindByID(id int64) (*models.User, error) {
	var u models.User
	err := r.db.First(&u, id).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &u, nil
}

// Create inserts a new user. Returns ErrDuplicate on a unique violation.
func (r *UserRepository) Create(u *models.User) error {
	if err := r.db.Create(u).Error; err != nil {
		if errors.Is(err, gorm.ErrDuplicatedKey) {
			return ErrDuplicate
		}
		return err
	}
	return nil
}

// FindByEmail returns a user by e-mail (case-insensitive).
func (r *UserRepository) FindByEmail(email string) (*models.User, error) {
	var u models.User
	err := r.db.Where("lower(email) = ? AND deleted_at IS NULL", strings.ToLower(email)).First(&u).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &u, nil
}

// UpdateFields applies field changes to a user.
func (r *UserRepository) UpdateFields(id int64, fields map[string]any) error {
	return r.db.Model(&models.User{}).Where("id = ?", id).Updates(fields).Error
}

// PhoneTakenByOther reports whether the normalized phone belongs to a user
// other than excludeID.
func (r *UserRepository) PhoneTakenByOther(phoneNumber string, excludeID int64) (bool, error) {
	var count int64
	err := r.db.Model(&models.User{}).
		Where(userhelpers.PhoneMatchSQL, userhelpers.NormalizePhone(phoneNumber)).
		Where("id <> ?", excludeID).
		Count(&count).Error
	return count > 0, err
}

// SoftDelete marks a user deleted (sets deleted_at).
func (r *UserRepository) SoftDelete(id int64) error {
	return r.db.Delete(&models.User{}, id).Error
}

// ForceDelete permanently removes a user row.
func (r *UserRepository) ForceDelete(id int64) error {
	return r.db.Unscoped().Delete(&models.User{}, id).Error
}

// AdminList returns users filtered by search/role (paginated, newest first).
// onlyTeam keeps users holding a role other than "user"; role matches one role.
func (r *UserRepository) AdminList(search string, role *string, onlyTeam bool, limit, offset int) ([]models.User, int64, error) {
	db := r.db.Model(&models.User{})

	if trimmed := strings.TrimSpace(search); trimmed != "" {
		like := "%" + strings.ToLower(trimmed) + "%"
		db = db.Where(
			"lower(name) LIKE ? OR lower(surname) LIKE ? OR lower(email) LIKE ? OR lower(phone) LIKE ? OR "+userhelpers.PhoneMatchSQL,
			like, like, like, like, userhelpers.NormalizePhone(trimmed),
		)
	}

	if onlyTeam {
		db = db.Where(
			`id IN (SELECT mhr.model_id FROM model_has_roles mhr
			        JOIN roles rr ON rr.id = mhr.role_id
			        WHERE mhr.model_type = ? AND rr.name <> 'user')`,
			rolepermissionmodels.UserMorph,
		)
	} else if role != nil && *role != "" {
		db = db.Where(
			`id IN (SELECT mhr.model_id FROM model_has_roles mhr
			        JOIN roles rr ON rr.id = mhr.role_id
			        WHERE mhr.model_type = ? AND rr.name = ?)`,
			rolepermissionmodels.UserMorph, *role,
		)
	}

	var total int64
	if err := db.Count(&total).Error; err != nil {
		return nil, 0, err
	}

	var users []models.User
	err := db.Order("created_at desc").Limit(limit).Offset(offset).Find(&users).Error
	return users, total, err
}

// FindByIDs returns the users whose id is in ids (empty input → empty result).
func (r *UserRepository) FindByIDs(ids []int64) ([]models.User, error) {
	if len(ids) == 0 {
		return nil, nil
	}
	var users []models.User
	err := r.db.Where("id IN ?", ids).Find(&users).Error
	return users, err
}
