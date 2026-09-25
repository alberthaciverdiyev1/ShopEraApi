package user

import (
	"errors"
	"strings"

	"gorm.io/gorm"
)

// Repository is the data access for users.
type Repository struct {
	db *gorm.DB
}

func NewRepository(db *gorm.DB) *Repository { return &Repository{db: db} }

// ExistsByPhone reports whether a user with the normalized phone exists.
func (r *Repository) ExistsByPhone(phoneNumber string) (bool, error) {
	var count int64
	err := r.db.Model(&User{}).Where(PhoneMatchSQL, NormalizePhone(phoneNumber)).Count(&count).Error
	return count > 0, err
}

// ExistsByEmail reports whether a user with the email exists (case-insensitive).
func (r *Repository) ExistsByEmail(email string) (bool, error) {
	var count int64
	err := r.db.Model(&User{}).Where("lower(email) = ?", strings.ToLower(email)).Count(&count).Error
	return count > 0, err
}

// FindByPhone returns a user by normalized phone.
func (r *Repository) FindByPhone(phoneNumber string) (*User, error) {
	var u User
	err := r.db.Where(PhoneMatchSQL, NormalizePhone(phoneNumber)).First(&u).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &u, nil
}

// FindByID returns a user by id.
func (r *Repository) FindByID(id int64) (*User, error) {
	var u User
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
func (r *Repository) Create(u *User) error {
	return r.db.Create(u).Error
}
