package product

import (
	"errors"

	"gorm.io/gorm"
)

// Repository is the data access for products.
type Repository struct {
	db *gorm.DB
}

func NewRepository(db *gorm.DB) *Repository { return &Repository{db: db} }

// List returns active products (paginated) and the total count.
func (r *Repository) List(filter Filter) ([]Product, int64, error) {
	query := r.db.Model(&Product{}).Where("is_active = ?", true)
	if filter.StoreID != nil {
		query = query.Where("store_id = ?", *filter.StoreID)
	}

	var total int64
	if err := query.Count(&total).Error; err != nil {
		return nil, 0, err
	}

	var products []Product
	err := query.
		Order("id desc").
		Limit(filter.PerPage).
		Offset((filter.Page - 1) * filter.PerPage).
		Find(&products).Error
	if err != nil {
		return nil, 0, err
	}
	return products, total, nil
}

// FindByID returns a product by id.
func (r *Repository) FindByID(id int64) (*Product, error) {
	var product Product
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
func (r *Repository) Create(product *Product) error {
	return r.db.Create(product).Error
}
