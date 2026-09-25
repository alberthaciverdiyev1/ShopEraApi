// Package repositories holds the data access, one repository per model.
package repositories

import (
	"errors"
	"strings"

	"gorm.io/gorm"

	"shopera/internal/modules/user"
	"shopera/internal/modules/user/models"
)

// UserRepository is the data access for the User model.
type UserRepository struct {
	db *gorm.DB
}

func NewUserRepository(db *gorm.DB) *UserRepository { return &UserRepository{db: db} }

// ExistsByPhone reports whether a user with the normalized phone exists.
func (r *UserRepository) ExistsByPhone(phoneNumber string) (bool, error) {
	var count int64
	err := r.db.Model(&models.User{}).Where(user.PhoneMatchSQL, user.NormalizePhone(phoneNumber)).Count(&count).Error
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
	err := r.db.Where(user.PhoneMatchSQL, user.NormalizePhone(phoneNumber)).First(&u).Error
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

// Create inserts a new user.
func (r *UserRepository) Create(u *models.User) error {
	return r.db.Create(u).Error
}
