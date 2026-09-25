// Package repositories holds the data access for the Favorite model.
package repositories

import (
	"gorm.io/gorm"

	"shopera/internal/helpers"
	"shopera/internal/modules/favorite/models"
	productmodels "shopera/internal/modules/product/models"
)

// FavoriteRepository is the data access for the Favorite model.
type FavoriteRepository struct {
	db *gorm.DB
}

func NewFavoriteRepository(db *gorm.DB) *FavoriteRepository { return &FavoriteRepository{db: db} }

// ListByUser returns the user's favorite products (paginated).
func (r *FavoriteRepository) ListByUser(userID int64, q helpers.Query) ([]productmodels.Product, int64, error) {
	countQuery := r.db.Table("products").
		Joins("JOIN user_favorites uf ON uf.product_id = products.id").
		Where("uf.user_id = ? AND products.deleted_at IS NULL", userID)

	var total int64
	if err := countQuery.Count(&total).Error; err != nil {
		return nil, 0, err
	}

	var items []productmodels.Product
	err := r.db.
		Preload("Brand").Preload("Category").Preload("Images").Preload("Colors").Preload("Sizes").
		Joins("JOIN user_favorites uf ON uf.product_id = products.id").
		Where("uf.user_id = ? AND products.deleted_at IS NULL", userID).
		Select("products.*").
		Order("uf.created_at desc").
		Limit(q.PerPage).Offset(q.Offset()).
		Find(&items).Error
	return items, total, err
}

// Exists reports whether the user already favorited the product.
func (r *FavoriteRepository) Exists(userID, productID int64) (bool, error) {
	var count int64
	err := r.db.Model(&models.Favorite{}).
		Where("user_id = ? AND product_id = ?", userID, productID).
		Count(&count).Error
	return count > 0, err
}

// Insert adds a favorite (idempotent).
func (r *FavoriteRepository) Insert(userID, productID int64) error {
	return r.db.Exec(
		`INSERT INTO user_favorites (user_id, product_id, created_at, updated_at)
		 VALUES (?, ?, NOW(), NOW())
		 ON CONFLICT (user_id, product_id) DO NOTHING`,
		userID, productID,
	).Error
}

// Remove deletes a favorite.
func (r *FavoriteRepository) Remove(userID, productID int64) error {
	return r.db.Exec(
		"DELETE FROM user_favorites WHERE user_id = ? AND product_id = ?",
		userID, productID,
	).Error
}

// ProductExists reports whether a (non-deleted) product exists.
func (r *FavoriteRepository) ProductExists(productID int64) (bool, error) {
	var count int64
	err := r.db.Model(&productmodels.Product{}).Where("id = ?", productID).Count(&count).Error
	return count > 0, err
}
