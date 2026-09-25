// Package repositories holds the data access for the Size model.
package repositories

import (
	"errors"

	"gorm.io/gorm"

	"shopera/internal/helpers"
	"shopera/internal/modules/size/models"
)

// SizeRepository is the data access for the Size model.
type SizeRepository struct {
	db *gorm.DB
}

func NewSizeRepository(db *gorm.DB) *SizeRepository { return &SizeRepository{db: db} }

// List returns a paginated size list.
func (r *SizeRepository) List(q helpers.Query) ([]models.Size, int64, error) {
	db := r.db.Model(&models.Size{})
	db = q.ApplySearch(db, helpers.SearchColumn{Column: "name"})
	db = q.ApplyWhereEach(db, "is_active")
	if _, ok := q.Params["is_active"]; !ok {
		db = db.Where("is_active = ?", true)
	}
	db = q.ApplyRange(db, "sort_order")
	db = q.WhereIn(db, "id", "ids")

	var total int64
	if err := db.Count(&total).Error; err != nil {
		return nil, 0, err
	}

	var items []models.Size
	err := q.ApplyPage(db.Order("sort_order desc").Order("id desc")).Find(&items).Error
	return items, total, err
}

// FindByID returns a size by id.
func (r *SizeRepository) FindByID(id int64) (*models.Size, error) {
	var s models.Size
	err := r.db.First(&s, id).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &s, nil
}

// ExistsByName reports whether another size already uses the name.
func (r *SizeRepository) ExistsByName(name string, exceptID int64) (bool, error) {
	var count int64
	q := r.db.Model(&models.Size{}).Where("name = ?", name)
	if exceptID > 0 {
		q = q.Where("id <> ?", exceptID)
	}
	err := q.Count(&count).Error
	return count > 0, err
}

// MaxSortOrder returns the current highest sort_order.
func (r *SizeRepository) MaxSortOrder() (int, error) {
	var max *int
	err := r.db.Model(&models.Size{}).Select("MAX(sort_order)").Scan(&max).Error
	if err != nil || max == nil {
		return 0, err
	}
	return *max, nil
}

// Create inserts a size.
func (r *SizeRepository) Create(s *models.Size) error {
	return r.db.Create(s).Error
}

// Update applies field changes to a size.
func (r *SizeRepository) Update(id int64, fields map[string]any) (*models.Size, error) {
	var s models.Size
	err := r.db.Transaction(func(tx *gorm.DB) error {
		if err := tx.First(&s, id).Error; err != nil {
			return err
		}
		if len(fields) > 0 {
			if err := tx.Model(&s).Updates(fields).Error; err != nil {
				return err
			}
		}
		return tx.First(&s, id).Error
	})
	if err != nil {
		return nil, err
	}
	return &s, nil
}

// Delete removes a size.
func (r *SizeRepository) Delete(id int64) error {
	return r.db.Delete(&models.Size{}, id).Error
}
