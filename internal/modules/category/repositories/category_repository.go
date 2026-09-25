// Package repositories holds the data access for the Category model.
package repositories

import (
	"errors"

	"gorm.io/gorm"

	"shopera/internal/helpers"
	"shopera/internal/modules/category/models"
)

// CategoryRepository is the data access for the Category model.
type CategoryRepository struct {
	db *gorm.DB
}

func NewCategoryRepository(db *gorm.DB) *CategoryRepository { return &CategoryRepository{db: db} }

// List returns categories. When onlyParents is true only root categories are returned.
func (r *CategoryRepository) List(query helpers.Query, onlyParents bool) ([]models.Category, error) {
	db := r.db.Model(&models.Category{})
	if onlyParents {
		db = db.Where("parent_id IS NULL")
	}
	db = query.ApplySearch(db,
		helpers.SearchColumn{Column: "name", Translatable: true},
		helpers.SearchColumn{Column: "description"},
	)
	db = query.ApplyWhereEach(db, "is_active")
	db = query.ApplyRange(db, "sort_order")
	db = query.ApplyWhereIn(db, "id", "ids")
	db = query.ApplyOrder(db, "sort_order")

	var items []models.Category
	err := db.Find(&items).Error
	return items, err
}

// FindByID returns a category by id.
func (r *CategoryRepository) FindByID(id int64) (*models.Category, error) {
	var c models.Category
	err := r.db.First(&c, id).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &c, nil
}

// Children returns the direct children of a category.
func (r *CategoryRepository) Children(parentID int64) ([]models.Category, error) {
	var items []models.Category
	err := r.db.Where("parent_id = ?", parentID).Order("sort_order desc").Find(&items).Error
	return items, err
}

// Create inserts a category.
func (r *CategoryRepository) Create(c *models.Category) error {
	return r.db.Create(c).Error
}

// Update applies field changes and, for top-level categories, reorders siblings.
func (r *CategoryRepository) Update(id int64, fields map[string]any, name map[string]string, newPos *int) (*models.Category, error) {
	var result models.Category
	err := r.db.Transaction(func(tx *gorm.DB) error {
		var c models.Category
		if err := tx.First(&c, id).Error; err != nil {
			return err
		}

		// Child categories keep their position.
		if c.ParentID != nil {
			delete(fields, "sort_order")
		} else if newPos != nil && *newPos != c.SortOrder {
			if *newPos < c.SortOrder {
				if err := tx.Model(&models.Category{}).
					Where("parent_id IS NULL AND sort_order BETWEEN ? AND ?", *newPos, c.SortOrder-1).
					UpdateColumn("sort_order", gorm.Expr("sort_order + 1")).Error; err != nil {
					return err
				}
			} else {
				if err := tx.Model(&models.Category{}).
					Where("parent_id IS NULL AND sort_order BETWEEN ? AND ?", c.SortOrder+1, *newPos).
					UpdateColumn("sort_order", gorm.Expr("sort_order - 1")).Error; err != nil {
					return err
				}
			}
			fields["sort_order"] = *newPos
		} else {
			delete(fields, "sort_order")
		}

		if len(fields) > 0 {
			if err := tx.Model(&c).Updates(fields).Error; err != nil {
				return err
			}
		}
		if name != nil {
			if err := tx.Model(&c).Update("name", name).Error; err != nil {
				return err
			}
		}
		return tx.First(&result, id).Error
	})
	if err != nil {
		return nil, err
	}
	return &result, nil
}

// Delete detaches products and removes the category.
func (r *CategoryRepository) Delete(id int64) error {
	return r.db.Transaction(func(tx *gorm.DB) error {
		if err := tx.Table("products").Where("category_id = ?", id).Update("category_id", nil).Error; err != nil {
			return err
		}
		return tx.Delete(&models.Category{}, id).Error
	})
}
