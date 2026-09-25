// Package repositories holds the data access for the Setting model.
package repositories

import (
	"errors"

	"gorm.io/gorm"

	"shopera/internal/modules/setting/models"
)

// SettingRepository is the data access for the Setting model.
type SettingRepository struct {
	db *gorm.DB
}

func NewSettingRepository(db *gorm.DB) *SettingRepository { return &SettingRepository{db: db} }

// All returns every settings row.
func (r *SettingRepository) All() ([]models.Setting, error) {
	var items []models.Setting
	err := r.db.Find(&items).Error
	return items, err
}

// First returns the first settings row (nil when the table is empty).
func (r *SettingRepository) First() (*models.Setting, error) {
	var s models.Setting
	err := r.db.First(&s).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &s, nil
}

// UpdateFirst updates the first settings row.
func (r *SettingRepository) UpdateFirst(fields map[string]any) (*models.Setting, error) {
	var s models.Setting
	err := r.db.Transaction(func(tx *gorm.DB) error {
		if err := tx.First(&s).Error; err != nil {
			return err
		}
		if len(fields) > 0 {
			if err := tx.Model(&s).Updates(fields).Error; err != nil {
				return err
			}
		}
		return tx.First(&s, s.ID).Error
	})
	if err != nil {
		return nil, err
	}
	return &s, nil
}
