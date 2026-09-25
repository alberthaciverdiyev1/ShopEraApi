// Package repositories holds the data access, one repository per model.
package repositories

import (
	"errors"

	"gorm.io/gorm"

	"shopera/internal/modules/product/models"
)

// ProductRepository is the data access for the Product model.
type ProductRepository struct {
	db *gorm.DB
}

func NewProductRepository(db *gorm.DB) *ProductRepository { return &ProductRepository{db: db} }

// List returns active products (paginated) and the total count.
func (r *ProductRepository) List(page, perPage int) ([]models.Product, int64, error) {
	query := r.db.Model(&models.Product{}).Where("is_active = ?", true)

	var total int64
	if err := query.Count(&total).Error; err != nil {
		return nil, 0, err
	}

	var products []models.Product
	err := query.Order("id desc").Limit(perPage).Offset((page - 1) * perPage).Find(&products).Error
	if err != nil {
		return nil, 0, err
	}
	return products, total, nil
}

// FindByID returns a product by id.
func (r *ProductRepository) FindByID(id int64) (*models.Product, error) {
	var product models.Product
	err := r.db.First(&product, id).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &product, nil
}

// Create inserts a product.
func (r *ProductRepository) Create(product *models.Product) error {
	return r.db.Create(product).Error
}
