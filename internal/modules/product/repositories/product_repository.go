// Package repositories holds the data access for the Product model.
package repositories

import (
	"errors"
	"strings"

	"gorm.io/gorm"

	"shopera/internal/helpers"
	"shopera/internal/modules/product/models"
)

// ProductRepository is the data access for the Product model.
type ProductRepository struct {
	db *gorm.DB
}

func NewProductRepository(db *gorm.DB) *ProductRepository { return &ProductRepository{db: db} }

// List returns active products (paginated) and the total count.
func (r *ProductRepository) List(q helpers.Query) ([]models.Product, int64, error) {
	db := r.buildListQuery(q)

	var total int64
	if err := db.Count(&total).Error; err != nil {
		return nil, 0, err
	}

	var products []models.Product
	err := q.ApplyPage(db.Order("id desc")).Find(&products).Error
	return products, total, err
}

// FindByID returns a product with its relations.
func (r *ProductRepository) FindByID(id int64) (*models.Product, error) {
	var p models.Product
	err := r.preload(r.db).First(&p, id).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &p, nil
}

// buildListQuery applies publicly-available scope and all list filters.
func (r *ProductRepository) buildListQuery(q helpers.Query) *gorm.DB {
	db := r.preload(r.db.Model(&models.Product{}).Where("products.is_active = ?", true))

	if v, ok := q.Params["is_suggest"]; ok && v != "" {
		db = db.Where("is_suggest = ?", v == "1" || v == "true")
	}
	if q.Params["discount"] != "" {
		db = db.Where("discount IS NOT NULL")
	}
	if v := q.Params["gender"]; v == "male" || v == "female" || v == "kids" {
		db = db.Where("gender = ?", v)
	}
	db = q.ApplyRange(db, "price")

	if ids := paramList(q, "category_ids"); len(ids) > 0 {
		db = db.Where("category_id IN ?", r.withChildCategories(ids))
	}
	if ids := paramList(q, "brand_ids"); len(ids) > 0 {
		db = db.Where("brand_id IN ?", ids)
	}
	if ids := paramList(q, "color_ids"); len(ids) > 0 {
		db = db.Where("EXISTS (SELECT 1 FROM color_product cp WHERE cp.product_id = products.id AND cp.color_id IN ?)", ids)
	}
	if ids := paramList(q, "size_ids"); len(ids) > 0 {
		db = db.Where("EXISTS (SELECT 1 FROM product_size ps WHERE ps.product_id = products.id AND ps.size_id IN ?)", ids)
	}

	db = q.ApplySearch(db,
		helpers.SearchColumn{Column: "title", Translatable: true},
		helpers.SearchColumn{Column: "description", Translatable: true},
		helpers.SearchColumn{Column: "sku"},
	)
	return db
}

func (r *ProductRepository) preload(db *gorm.DB) *gorm.DB {
	return db.Preload("Colors").Preload("Sizes").Preload("Images").
		Preload("Videos").Preload("Category").Preload("Brand")
}

// withChildCategories expands the given category ids with all descendants.
func (r *ProductRepository) withChildCategories(ids []string) []string {
	all := append([]string{}, ids...)
	frontier := ids
	for len(frontier) > 0 {
		var children []string
		r.db.Table("categories").Where("parent_id IN ?", frontier).Pluck("id", &children)
		if len(children) == 0 {
			break
		}
		all = append(all, children...)
		frontier = children
	}
	return all
}

func paramList(q helpers.Query, param string) []string {
	raw := strings.TrimSpace(q.Params[param])
	if raw == "" {
		return nil
	}
	parts := strings.Split(raw, ",")
	out := make([]string, 0, len(parts))
	for _, p := range parts {
		if p = strings.TrimSpace(p); p != "" {
			out = append(out, p)
		}
	}
	return out
}

// Create inserts a product.
func (r *ProductRepository) Create(p *models.Product) error {
	return r.db.Create(p).Error
}
