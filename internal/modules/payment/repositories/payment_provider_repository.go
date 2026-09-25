// Package repositories holds the data access for payment providers.
package repositories

import (
	"errors"

	"gorm.io/gorm"

	"shopera/internal/modules/payment/models"
)

// PaymentProviderRepository is the data access for PaymentProvider.
type PaymentProviderRepository struct {
	db *gorm.DB
}

func NewPaymentProviderRepository(db *gorm.DB) *PaymentProviderRepository {
	return &PaymentProviderRepository{db: db}
}

// All returns providers ordered by sort_order.
func (r *PaymentProviderRepository) All(activeOnly bool) ([]models.PaymentProvider, error) {
	db := r.db.Model(&models.PaymentProvider{})
	if activeOnly {
		db = db.Where("is_active = ?", true)
	}
	var items []models.PaymentProvider
	err := db.Order("sort_order asc").Find(&items).Error
	return items, err
}

// FindByKey returns a provider by its key.
func (r *PaymentProviderRepository) FindByKey(key string) (*models.PaymentProvider, error) {
	var p models.PaymentProvider
	err := r.db.Where("key = ?", key).First(&p).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &p, nil
}

// Active returns the first active provider (lowest sort_order).
func (r *PaymentProviderRepository) Active() (*models.PaymentProvider, error) {
	var p models.PaymentProvider
	err := r.db.Where("is_active = ?", true).Order("sort_order asc").First(&p).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &p, nil
}

// Create inserts a provider.
func (r *PaymentProviderRepository) Create(p *models.PaymentProvider) error {
	return r.db.Create(p).Error
}

// Update applies field changes to a provider.
func (r *PaymentProviderRepository) Update(id int64, fields map[string]any) (*models.PaymentProvider, error) {
	var p models.PaymentProvider
	err := r.db.Transaction(func(tx *gorm.DB) error {
		if err := tx.First(&p, id).Error; err != nil {
			return err
		}
		if len(fields) > 0 {
			if err := tx.Model(&p).Updates(fields).Error; err != nil {
				return err
			}
		}
		return tx.First(&p, id).Error
	})
	if err != nil {
		return nil, err
	}
	return &p, nil
}
