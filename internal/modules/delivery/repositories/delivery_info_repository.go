package repositories

import (
	"errors"

	"gorm.io/gorm"

	"shopera/internal/modules/delivery/models"
)

// DeliveryInfoRepository is the data access for delivery info.
type DeliveryInfoRepository struct {
	db *gorm.DB
}

func NewDeliveryInfoRepository(db *gorm.DB) *DeliveryInfoRepository {
	return &DeliveryInfoRepository{db: db}
}

// List returns delivery info, optionally filtered by a set of types.
func (r *DeliveryInfoRepository) List(types []string) ([]models.DeliveryInfo, error) {
	db := r.db.Model(&models.DeliveryInfo{})
	if len(types) > 0 {
		db = db.Where("type IN ?", types)
	}
	var items []models.DeliveryInfo
	err := db.Order("id asc").Find(&items).Error
	return items, err
}

// FindByType returns a delivery info by type (upper-cased).
func (r *DeliveryInfoRepository) FindByType(deliveryType string) (*models.DeliveryInfo, error) {
	var d models.DeliveryInfo
	err := r.db.Where("type = ?", deliveryType).First(&d).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &d, nil
}

// Update applies field changes.
func (r *DeliveryInfoRepository) Update(id int64, fields map[string]any) (*models.DeliveryInfo, error) {
	var d models.DeliveryInfo
	err := r.db.Transaction(func(tx *gorm.DB) error {
		if err := tx.First(&d, id).Error; err != nil {
			return err
		}
		// Map fields go through a struct update so `serializer:json` is applied.
		changed := []string{}
		if m, ok := fields["description"].(map[string]string); ok {
			d.Description = m
			changed = append(changed, "description")
			delete(fields, "description")
		}
		if len(fields) > 0 {
			if err := tx.Model(&d).Updates(fields).Error; err != nil {
				return err
			}
		}
		if len(changed) > 0 {
			if err := tx.Model(&d).Select(changed).Updates(d).Error; err != nil {
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
