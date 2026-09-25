// Package repositories holds the data access for the Color model.
package repositories

import (
	"errors"

	"gorm.io/gorm"

	"shopera/internal/helpers"
	"shopera/internal/modules/color/models"
)

// ColorRepository is the data access for the Color model.
type ColorRepository struct {
	db *gorm.DB
}

func NewColorRepository(db *gorm.DB) *ColorRepository { return &ColorRepository{db: db} }

// List returns a paginated color list.
func (r *ColorRepository) List(q helpers.Query) ([]models.Color, int64, error) {
	db := r.db.Model(&models.Color{})
	db = q.ApplySearch(db, helpers.SearchColumn{Column: "name"})
	db = q.ApplyWhereEach(db, "is_active")
	// Default to active-only for public consumers; ?all=1 (admin) returns everything.
	if _, ok := q.Params["is_active"]; !ok && !q.All {
		db = db.Where("is_active = ?", true)
	}
	db = q.ApplyRange(db, "sort_order")
	db = q.WhereIn(db, "id", "ids")

	var total int64
	if err := db.Count(&total).Error; err != nil {
		return nil, 0, err
	}

	var items []models.Color
	err := q.ApplyPage(db.Order("sort_order desc").Order("id desc")).Find(&items).Error
	return items, total, err
}

// FindByID returns a color by id.
func (r *ColorRepository) FindByID(id int64) (*models.Color, error) {
	var c models.Color
	err := r.db.First(&c, id).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &c, nil
}

// ExistsByName reports whether another color already uses the name.
func (r *ColorRepository) ExistsByName(name string, exceptID int64) (bool, error) {
	var count int64
	q := r.db.Model(&models.Color{}).Where("name = ?", name)
	if exceptID > 0 {
		q = q.Where("id <> ?", exceptID)
	}
	err := q.Count(&count).Error
	return count > 0, err
}

// MaxSortOrder returns the current highest sort_order.
func (r *ColorRepository) MaxSortOrder() (int, error) {
	var max *int
	err := r.db.Model(&models.Color{}).Select("MAX(sort_order)").Scan(&max).Error
	if err != nil || max == nil {
		return 0, err
	}
	return *max, nil
}

// Create inserts a color.
func (r *ColorRepository) Create(c *models.Color) error {
	return r.db.Create(c).Error
}

// Update applies field changes to a color.
func (r *ColorRepository) Update(id int64, fields map[string]any) (*models.Color, error) {
	var c models.Color
	err := r.db.Transaction(func(tx *gorm.DB) error {
		if err := tx.First(&c, id).Error; err != nil {
			return err
		}
		if len(fields) > 0 {
			if err := tx.Model(&c).Updates(fields).Error; err != nil {
				return err
			}
		}
		return tx.First(&c, id).Error
	})
	if err != nil {
		return nil, err
	}
	return &c, nil
}

// Delete detaches products (pivot) and removes the color.
func (r *ColorRepository) Delete(id int64) error {
	return r.db.Transaction(func(tx *gorm.DB) error {
		if err := tx.Exec("DELETE FROM color_product WHERE color_id = ?", id).Error; err != nil {
			return err
		}
		return tx.Delete(&models.Color{}, id).Error
	})
}
