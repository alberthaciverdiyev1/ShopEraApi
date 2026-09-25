package repositories

import (
	"errors"

	"gorm.io/gorm"

	"shopera/internal/helpers"
	"shopera/internal/modules/delivery/models"
)

// DeliveryPriceRepository is the data access for delivery prices.
type DeliveryPriceRepository struct {
	db *gorm.DB
}

func NewDeliveryPriceRepository(db *gorm.DB) *DeliveryPriceRepository {
	return &DeliveryPriceRepository{db: db}
}

// List returns delivery prices (paginated); search matches city key/name.
func (r *DeliveryPriceRepository) List(q helpers.Query, isAdmin bool) ([]models.DeliveryPrice, int64, error) {
	db := r.db.Model(&models.DeliveryPrice{})

	if q.Search != "" {
		like := "%" + q.Search + "%"
		var keys []string
		if err := r.db.Table("cities").
			Where("lower(key) LIKE ? OR lower(name) LIKE ?", like, like).
			Pluck("key", &keys).Error; err != nil {
			return nil, 0, err
		}
		db = db.Where("city_name IN ?", keys)
	}
	if !isAdmin {
		db = db.Where("is_active = ?", true)
	}

	var total int64
	if err := db.Count(&total).Error; err != nil {
		return nil, 0, err
	}

	var items []models.DeliveryPrice
	err := q.ApplyPage(db.Order("price desc")).Find(&items).Error
	return items, total, err
}

// FindByID returns a delivery price by id.
func (r *DeliveryPriceRepository) FindByID(id int64) (*models.DeliveryPrice, error) {
	var d models.DeliveryPrice
	err := r.db.First(&d, id).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &d, nil
}

// FindActiveByCityKey returns the active delivery for a city key.
func (r *DeliveryPriceRepository) FindActiveByCityKey(key string) (*models.DeliveryPrice, error) {
	var d models.DeliveryPrice
	err := r.db.Where("is_active = ? AND city_name = ?", true, key).First(&d).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &d, nil
}

// FindByCityKeyIncludingTrashed looks up a delivery by city key including soft-deleted rows.
func (r *DeliveryPriceRepository) FindByCityKeyIncludingTrashed(key string) (*models.DeliveryPrice, error) {
	var d models.DeliveryPrice
	err := r.db.Unscoped().Where("city_name = ?", key).First(&d).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &d, nil
}

// Create inserts a delivery price.
func (r *DeliveryPriceRepository) Create(d *models.DeliveryPrice) error {
	return r.db.Create(d).Error
}

// RestoreUpdate restores a soft-deleted row and applies changes.
func (r *DeliveryPriceRepository) RestoreUpdate(d *models.DeliveryPrice, fields map[string]any) (*models.DeliveryPrice, error) {
	var result models.DeliveryPrice
	err := r.db.Transaction(func(tx *gorm.DB) error {
		if err := tx.Unscoped().Model(&models.DeliveryPrice{}).Where("id = ?", d.ID).Update("deleted_at", nil).Error; err != nil {
			return err
		}
		if len(fields) > 0 {
			if err := tx.Model(&models.DeliveryPrice{}).Where("id = ?", d.ID).Updates(fields).Error; err != nil {
				return err
			}
		}
		return tx.First(&result, d.ID).Error
	})
	if err != nil {
		return nil, err
	}
	return &result, nil
}

// Update applies field changes.
func (r *DeliveryPriceRepository) Update(id int64, fields map[string]any) (*models.DeliveryPrice, error) {
	var d models.DeliveryPrice
	err := r.db.Transaction(func(tx *gorm.DB) error {
		if err := tx.First(&d, id).Error; err != nil {
			return err
		}
		if len(fields) > 0 {
			if err := tx.Model(&d).Updates(fields).Error; err != nil {
				return err
			}
		}
		return tx.First(&d, id).Error
	})
	if err != nil {
		return nil, err
	}
	return &d, nil
}

// Delete soft-deletes a delivery price.
func (r *DeliveryPriceRepository) Delete(id int64) error {
	return r.db.Delete(&models.DeliveryPrice{}, id).Error
}
