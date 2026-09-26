// Package repositories holds the data access for the Filter models.
package repositories

import (
	"errors"

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

// ByCategory returns the distinct filters used by the products of a category
// (TicarXCaspian: products -> filters collapsed).
func (r *FilterRepository) ByCategory(categoryID int64) ([]models.Filter, error) {
	var items []models.Filter
	err := r.db.
		Distinct("filters.*").
		Joins("JOIN product_filters pf ON pf.filter_id = filters.id").
		Joins("JOIN products p ON p.id = pf.product_id").
		Where("p.category_id = ? AND p.deleted_at IS NULL", categoryID).
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

// FindByID returns a filter by id (nil when missing).
func (r *FilterRepository) FindByID(id int64) (*models.Filter, error) {
	var f models.Filter
	err := r.db.First(&f, id).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &f, nil
}

// Create inserts a filter.
func (r *FilterRepository) Create(f *models.Filter) error {
	return r.db.Select("*").Create(f).Error
}

// Update applies changes; json-serialized maps/slices go through a struct update.
func (r *FilterRepository) Update(id int64, fields map[string]any) (*models.Filter, error) {
	var f models.Filter
	err := r.db.Transaction(func(tx *gorm.DB) error {
		if err := tx.First(&f, id).Error; err != nil {
			return err
		}
		changed := []string{}
		if m, ok := fields["title"].(map[string]string); ok {
			f.Title = m
			changed = append(changed, "title")
			delete(fields, "title")
		}
		if o, ok := fields["options"].([]string); ok {
			f.Options = o
			changed = append(changed, "options")
			delete(fields, "options")
		}
		if len(fields) > 0 {
			if err := tx.Model(&f).Updates(fields).Error; err != nil {
				return err
			}
		}
		if len(changed) > 0 {
			if err := tx.Model(&f).Select(changed).Updates(f).Error; err != nil {
				return err
			}
		}
		return tx.First(&f, id).Error
	})
	if err != nil {
		return nil, err
	}
	return &f, nil
}

// Delete removes a filter (cascades to category/product links).
func (r *FilterRepository) Delete(id int64) error {
	return r.db.Delete(&models.Filter{}, id).Error
}

// CategoryIDs returns the category ids a filter is attached to.
func (r *FilterRepository) CategoryIDs(filterID int64) ([]int64, error) {
	var ids []int64
	err := r.db.Model(&models.CategoryFilter{}).
		Where("filter_id = ?", filterID).
		Pluck("category_id", &ids).Error
	return ids, err
}

// SetCategories replaces the categories attached to a filter.
func (r *FilterRepository) SetCategories(filterID int64, categoryIDs []int64) error {
	return r.db.Transaction(func(tx *gorm.DB) error {
		if err := tx.Where("filter_id = ?", filterID).Delete(&models.CategoryFilter{}).Error; err != nil {
			return err
		}
		for _, cid := range categoryIDs {
			link := models.CategoryFilter{FilterID: filterID, CategoryID: cid}
			if err := tx.Create(&link).Error; err != nil {
				return err
			}
		}
		return nil
	})
}

// ProductValues returns a product's filter values.
func (r *FilterRepository) ProductValues(productID int64) ([]models.ProductFilter, error) {
	var items []models.ProductFilter
	err := r.db.Where("product_id = ?", productID).Order("filter_id asc").Find(&items).Error
	return items, err
}

// SetProductValues replaces a product's filter values.
func (r *FilterRepository) SetProductValues(productID int64, items []models.ProductFilter) error {
	return r.db.Transaction(func(tx *gorm.DB) error {
		if err := tx.Where("product_id = ?", productID).Delete(&models.ProductFilter{}).Error; err != nil {
			return err
		}
		for _, item := range items {
			row := models.ProductFilter{ProductID: productID, FilterID: item.FilterID, Value: item.Value}
			if err := tx.Create(&row).Error; err != nil {
				return err
			}
		}
		return nil
	})
}
