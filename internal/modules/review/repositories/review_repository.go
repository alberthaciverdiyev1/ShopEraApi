// Package repositories holds the data access for the Review model.
package repositories

import (
	"errors"

	"gorm.io/gorm"

	"shopera/internal/helpers"
	"shopera/internal/modules/review/models"
)

// ReviewRepository is the data access for the Review model.
type ReviewRepository struct {
	db *gorm.DB
}

func NewReviewRepository(db *gorm.DB) *ReviewRepository { return &ReviewRepository{db: db} }

// ListByProduct returns approved reviews of a product (paginated).
func (r *ReviewRepository) ListByProduct(productID int64, q helpers.Query) ([]models.Review, int64, error) {
	db := r.preload(r.db.Model(&models.Review{})).
		Where("product_id = ?", productID).
		Where("status = ?", models.StatusApproved)

	var total int64
	if err := db.Count(&total).Error; err != nil {
		return nil, 0, err
	}

	var items []models.Review
	err := q.ApplyPage(db.Order("id desc")).Find(&items).Error
	return items, total, err
}

// ListAdmin returns all reviews (paginated), optionally filtered by status value.
func (r *ReviewRepository) ListAdmin(status *string, q helpers.Query) ([]models.Review, int64, error) {
	db := r.preload(r.db.Model(&models.Review{}))
	if status != nil {
		db = db.Where("status = ?", *status)
	}

	var total int64
	if err := db.Count(&total).Error; err != nil {
		return nil, 0, err
	}

	var items []models.Review
	err := q.ApplyPage(db.Order("created_at desc")).Find(&items).Error
	return items, total, err
}

// IncrementProductViews bumps a product's view counter (Laravel incremented it
// when the product's reviews were listed).
func (r *ReviewRepository) IncrementProductViews(productID int64) error {
	return r.db.Table("products").Where("id = ?", productID).
		UpdateColumn("views", gorm.Expr("views + 1")).Error
}

// FindByID returns a review by id.
func (r *ReviewRepository) FindByID(id int64) (*models.Review, error) {
	var review models.Review
	err := r.preload(r.db).First(&review, id).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &review, nil
}

// Create inserts a review.
func (r *ReviewRepository) Create(review *models.Review) error {
	return r.db.Create(review).Error
}

// UpdateStatus sets a review's status.
func (r *ReviewRepository) UpdateStatus(id int64, status string) error {
	return r.db.Model(&models.Review{}).Where("id = ?", id).Update("status", status).Error
}

// Delete removes a review.
func (r *ReviewRepository) Delete(id int64) error {
	return r.db.Delete(&models.Review{}, id).Error
}

func (r *ReviewRepository) preload(db *gorm.DB) *gorm.DB {
	return db.Preload("User").
		Preload("Product.Category").
		Preload("Product.Brand").
		Preload("Product.Images")
}
