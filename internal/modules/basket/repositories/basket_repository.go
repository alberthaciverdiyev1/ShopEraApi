// Package repositories holds the data access for the Basket model.
package repositories

import (
	"errors"

	"gorm.io/gorm"

	"shopera/internal/helpers"
	"shopera/internal/modules/basket/models"
	productmodels "shopera/internal/modules/product/models"
)

// BasketRepository is the data access for the Basket model.
type BasketRepository struct {
	db *gorm.DB
}

func NewBasketRepository(db *gorm.DB) *BasketRepository { return &BasketRepository{db: db} }

// List returns the user's open basket items (newest first).
func (r *BasketRepository) List(userID int64, q helpers.Query) ([]models.Basket, error) {
	var items []models.Basket
	err := r.preload(r.db.Model(&models.Basket{})).
		Where("user_id = ? AND is_ordered = ?", userID, false).
		Order("id desc").
		Find(&items).Error

	// Note: basket lists are small; pagination intentionally skipped (full data).
	_ = q
	return items, err
}

// FindOwned returns one of the user's baskets.
func (r *BasketRepository) FindOwned(userID, id int64) (*models.Basket, error) {
	var b models.Basket
	err := r.preload(r.db).Where("user_id = ?", userID).First(&b, id).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &b, nil
}

// Create inserts a basket item.
func (r *BasketRepository) Create(b *models.Basket) error {
	return r.db.Create(b).Error
}

// Update applies field changes to a basket item.
func (r *BasketRepository) Update(id int64, fields map[string]any) error {
	return r.db.Model(&models.Basket{}).Where("id = ?", id).Updates(fields).Error
}

// Delete removes a basket item.
func (r *BasketRepository) Delete(id int64) error {
	return r.db.Delete(&models.Basket{}, id).Error
}

// QuantitySum returns how many units of a product the user already has.
func (r *BasketRepository) QuantitySum(userID, productID int64) (int, error) {
	var sum *int
	err := r.db.Model(&models.Basket{}).
		Where("user_id = ? AND product_id = ?", userID, productID).
		Select("SUM(quantity)").Scan(&sum).Error
	if err != nil || sum == nil {
		return 0, err
	}
	return *sum, nil
}

// ProductForBasket returns a published product with the relations the basket needs.
func (r *BasketRepository) ProductForBasket(productID int64) (*productmodels.Product, error) {
	var p productmodels.Product
	err := r.db.
		Preload("Brand").Preload("Category").Preload("Images").
		Preload("Colors").Preload("Sizes").
		Where("is_active = ?", true).
		First(&p, productID).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &p, nil
}

func (r *BasketRepository) preload(db *gorm.DB) *gorm.DB {
	return db.Preload("Product.Brand").Preload("Product.Category").
		Preload("Product.Images").Preload("Product.Colors").Preload("Product.Sizes")
}
