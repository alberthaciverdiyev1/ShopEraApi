// Package repositories holds the data access for the Address model.
package repositories

import (
	"errors"

	"gorm.io/gorm"

	"shopera/internal/modules/address/models"
)

// AddressRepository is the data access for the Address model.
type AddressRepository struct {
	db *gorm.DB
}

func NewAddressRepository(db *gorm.DB) *AddressRepository { return &AddressRepository{db: db} }

// ListByUser returns all of the user's addresses.
func (r *AddressRepository) ListByUser(userID int64) ([]models.Address, error) {
	var items []models.Address
	err := r.db.Where("user_id = ?", userID).Order("id desc").Find(&items).Error
	return items, err
}

// FindOwned returns one of the user's addresses.
func (r *AddressRepository) FindOwned(userID, id int64) (*models.Address, error) {
	var a models.Address
	err := r.db.Where("user_id = ?", userID).First(&a, id).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &a, nil
}

// HasAny reports whether the user already has an address.
func (r *AddressRepository) HasAny(userID int64) (bool, error) {
	var count int64
	err := r.db.Model(&models.Address{}).Where("user_id = ?", userID).Count(&count).Error
	return count > 0, err
}

// Create inserts an address.
func (r *AddressRepository) Create(a *models.Address) error {
	return r.db.Create(a).Error
}

// Update applies changes to an address.
func (r *AddressRepository) Update(id int64, fields map[string]any) (*models.Address, error) {
	var a models.Address
	err := r.db.Transaction(func(tx *gorm.DB) error {
		if err := tx.First(&a, id).Error; err != nil {
			return err
		}
		if len(fields) > 0 {
			if err := tx.Model(&a).Updates(fields).Error; err != nil {
				return err
			}
		}
		return tx.First(&a, id).Error
	})
	if err != nil {
		return nil, err
	}
	return &a, nil
}

// UnsetOtherDefaults clears is_default for the user's other addresses.
func (r *AddressRepository) UnsetOtherDefaults(userID, exceptID int64) error {
	return r.db.Model(&models.Address{}).
		Where("user_id = ? AND id <> ? AND is_default = ?", userID, exceptID, true).
		Update("is_default", false).Error
}

// Delete removes an address.
func (r *AddressRepository) Delete(id int64) error {
	return r.db.Delete(&models.Address{}, id).Error
}

// FindDefault returns the user's default address.
func (r *AddressRepository) FindDefault(userID int64) (*models.Address, error) {
	var a models.Address
	err := r.db.Where("user_id = ? AND is_default = ?", userID, true).First(&a).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &a, nil
}
