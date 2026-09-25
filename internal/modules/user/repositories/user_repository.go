// Package repositories holds the data access, one repository per model.
package repositories

import (
	"errors"
	"strings"

	"gorm.io/gorm"

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

// FindByIDs returns the users whose id is in ids (empty input → empty result).
func (r *UserRepository) FindByIDs(ids []int64) ([]models.User, error) {
	if len(ids) == 0 {
		return nil, nil
	}
	var users []models.User
	err := r.db.Where("id IN ?", ids).Find(&users).Error
	return users, err
}
