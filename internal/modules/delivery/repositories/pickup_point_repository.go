package repositories

import (
	"errors"

	"gorm.io/gorm"

	"shopera/internal/helpers"
	"shopera/internal/modules/delivery/models"
)

// PickupPointRepository is the data access for pickup points.
type PickupPointRepository struct {
	db *gorm.DB
}

func NewPickupPointRepository(db *gorm.DB) *PickupPointRepository {
	return &PickupPointRepository{db: db}
}

// List returns pickup points (paginated).
func (r *PickupPointRepository) List(q helpers.Query, isActive *bool, isAdmin bool) ([]models.PickupPoint, int64, error) {
	db := r.db.Model(&models.PickupPoint{})
	if isActive != nil {
		db = db.Where("is_active = ?", *isActive)
	} else if !isAdmin {
		db = db.Where("is_active = ?", true)
	}
	db = q.ApplySearch(db, helpers.SearchColumn{Column: "name"})

	var total int64
	if err := db.Count(&total).Error; err != nil {
		return nil, 0, err
	}

	var items []models.PickupPoint
	err := q.ApplyPage(db.Order("id desc")).Find(&items).Error
	return items, total, err
}

// FindByID returns a pickup point (active only when activeOnly).
func (r *PickupPointRepository) FindByID(id int64, activeOnly bool) (*models.PickupPoint, error) {
	var p models.PickupPoint
	db := r.db
	if activeOnly {
		db = db.Where("is_active = ?", true)
	}
	err := db.First(&p, id).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &p, nil
}

// FindByNameIncludingTrashed looks up a pickup point by name, trashed included.
func (r *PickupPointRepository) FindByNameIncludingTrashed(name string) (*models.PickupPoint, error) {
	var p models.PickupPoint
	err := r.db.Unscoped().Where("name = ?", name).First(&p).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &p, nil
}

// ExistsByName reports whether another pickup point uses the name.
func (r *PickupPointRepository) ExistsByName(name string, exceptID int64) (bool, error) {
	var count int64
	q := r.db.Model(&models.PickupPoint{}).Where("name = ?", name)
	if exceptID > 0 {
		q = q.Where("id <> ?", exceptID)
	}
	err := q.Count(&count).Error
	return count > 0, err
}

// Create inserts a pickup point.
func (r *PickupPointRepository) Create(p *models.PickupPoint) error {
	return r.db.Create(p).Error
}

// RestoreUpdate restores a soft-deleted pickup point and applies changes.
func (r *PickupPointRepository) RestoreUpdate(p *models.PickupPoint, fields map[string]any) (*models.PickupPoint, error) {
	var result models.PickupPoint
	err := r.db.Transaction(func(tx *gorm.DB) error {
		if err := tx.Unscoped().Model(&models.PickupPoint{}).Where("id = ?", p.ID).Update("deleted_at", nil).Error; err != nil {
			return err
		}
		if len(fields) > 0 {
			if err := tx.Model(&models.PickupPoint{}).Where("id = ?", p.ID).Updates(fields).Error; err != nil {
				return err
			}
		}
		return tx.First(&result, p.ID).Error
	})
	if err != nil {
		return nil, err
	}
	return &result, nil
}

// Update applies field changes.
func (r *PickupPointRepository) Update(id int64, fields map[string]any) (*models.PickupPoint, error) {
	var p models.PickupPoint
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

// Delete soft-deletes a pickup point.
func (r *PickupPointRepository) Delete(id int64) error {
	return r.db.Delete(&models.PickupPoint{}, id).Error
}
