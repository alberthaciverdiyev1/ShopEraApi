// Package repositories holds the data access for the Filter models.
package repositories

import (
	"gorm.io/gorm"

	"shopera/internal/modules/filter/models"
)

// FilterRepository is the data access for dynamic filters.
type FilterRepository struct {
	db *gorm.DB
}

func NewFilterRepository(db *gorm.DB) *FilterRepository { return &FilterRepository{db: db} }

// All returns every filter.
func (r *FilterRepository) All() ([]models.Filter, error) {
	var items []models.Filter
	err := r.db.Order("id asc").Find(&items).Error
	return items, err
}

// ByCategory returns the filters assigned to a category.
func (r *FilterRepository) ByCategory(categoryID int64) ([]models.Filter, error) {
	var items []models.Filter
	err := r.db.
		Joins("JOIN category_filters cf ON cf.filter_id = filters.id").
		Where("cf.category_id = ?", categoryID).
		Order("filters.id asc").
		Find(&items).Error
	return items, err
}

// CategoryValues returns the distinct values present among the products of a
// category, keyed by filter id.
func (r *FilterRepository) CategoryValues(categoryID int64) (map[int64][]string, error) {
	type row struct {
		FilterID int64
		Value    string
	}
	var rows []row
	err := r.db.
		Table("product_filters pf").
		Select("pf.filter_id as filter_id, pf.value as value").
		Joins("JOIN products p ON p.id = pf.product_id").
		Where("p.category_id = ? AND p.deleted_at IS NULL AND pf.value IS NOT NULL AND pf.value <> ''", categoryID).
		Distinct().
		Scan(&rows).Error
	if err != nil {
		return nil, err
	}

	out := map[int64][]string{}
	for _, r := range rows {
		out[r.FilterID] = append(out[r.FilterID], r.Value)
	}
	return out, nil
}
