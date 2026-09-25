// Package repositories holds the data access for the Banner model.
package repositories

import (
	"errors"

	"gorm.io/gorm"

	"shopera/internal/helpers"
	"shopera/internal/modules/banner/models"
)

// BannerRepository is the data access for the Banner model.
type BannerRepository struct {
	db *gorm.DB
}

func NewBannerRepository(db *gorm.DB) *BannerRepository { return &BannerRepository{db: db} }

// List returns banners (newest first), paginated unless full data is requested.
func (r *BannerRepository) List(q helpers.Query, bannerType *string) ([]models.Banner, int64, error) {
	db := r.db.Model(&models.Banner{})
	if bannerType != nil {
		db = db.Where("type = ?", *bannerType)
	}

	var total int64
	if err := db.Count(&total).Error; err != nil {
		return nil, 0, err
	}

	var items []models.Banner
	err := q.ApplyPage(db.Order("created_at desc").Order("id desc")).Find(&items).Error
	return items, total, err
}

// FindByID returns a banner by id.
func (r *BannerRepository) FindByID(id int64) (*models.Banner, error) {
	var b models.Banner
	err := r.db.First(&b, id).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &b, nil
}

// Create inserts a banner.
func (r *BannerRepository) Create(b *models.Banner) error {
	return r.db.Create(b).Error
}

// Delete removes a banner.
func (r *BannerRepository) Delete(id int64) error {
	return r.db.Delete(&models.Banner{}, id).Error
}
