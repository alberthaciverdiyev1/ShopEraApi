// Package repositories holds the data access for the Brand model.
package repositories

import (
	"errors"

	"gorm.io/gorm"

	"shopera/internal/helpers"
	"shopera/internal/modules/brand/models"
)

// BrandRepository is the data access for the Brand model.
type BrandRepository struct {
	db *gorm.DB
}

func NewBrandRepository(db *gorm.DB) *BrandRepository { return &BrandRepository{db: db} }

// List returns a paginated brand list using the shared query helper.
func (r *BrandRepository) List(q helpers.Query, isActive *bool) ([]models.Brand, int64, error) {
	db := r.db.Model(&models.Brand{})
	db = q.ApplySearch(db, helpers.SearchColumn{Column: "name"})
	if isActive != nil {
		db = db.Where("is_active = ?", *isActive)
	} else {
		db = db.Where("is_active = ?", true)
	}

	var total int64
	if err := db.Count(&total).Error; err != nil {
		return nil, 0, err
	}

	var brands []models.Brand
	err := q.ApplyPage(q.ApplyOrder(db, "id")).Find(&brands).Error
	return brands, total, err
}

// FindByID returns a brand by id.
func (r *BrandRepository) FindByID(id int64) (*models.Brand, error) {
	var b models.Brand
	err := r.db.First(&b, id).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &b, nil
}

// Create inserts a brand.
func (r *BrandRepository) Create(b *models.Brand) error {
	return r.db.Create(b).Error
}

// Update applies field changes to a brand.
func (r *BrandRepository) Update(id int64, fields map[string]any) (*models.Brand, error) {
	var b models.Brand
	err := r.db.Transaction(func(tx *gorm.DB) error {
		if err := tx.First(&b, id).Error; err != nil {
			return err
		}
		if len(fields) > 0 {
			if err := tx.Model(&b).Updates(fields).Error; err != nil {
				return err
			}
		}
		return tx.First(&b, id).Error
	})
	if err != nil {
		return nil, err
	}
	return &b, nil
}

// Delete detaches products and removes the brand.
func (r *BrandRepository) Delete(id int64) error {
	return r.db.Transaction(func(tx *gorm.DB) error {
		if err := tx.Table("products").Where("brand_id = ?", id).Update("brand_id", nil).Error; err != nil {
			return err
		}
		return tx.Delete(&models.Brand{}, id).Error
	})
}
