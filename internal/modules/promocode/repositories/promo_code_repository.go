// Package repositories holds the data access for promo codes.
package repositories

import (
	"errors"
	"strings"

	"gorm.io/gorm"

	"shopera/internal/helpers"
	"shopera/internal/modules/promocode/models"
)

// PromoCodeRepository is the data access for promo codes.
type PromoCodeRepository struct {
	db *gorm.DB
}

func NewPromoCodeRepository(db *gorm.DB) *PromoCodeRepository { return &PromoCodeRepository{db: db} }

// List returns promo codes (active by default), newest first.
func (r *PromoCodeRepository) List(q helpers.Query, isActive *bool) ([]models.PromoCode, error) {
	db := r.db.Model(&models.PromoCode{})
	if search := strings.TrimSpace(q.Search); search != "" {
		db = db.Where("code ILIKE ?", "%"+search+"%")
	}
	if isActive != nil {
		db = db.Where("is_active = ?", *isActive)
	} else {
		db = db.Where("is_active = ?", true)
	}
	var items []models.PromoCode
	err := db.Order("created_at desc").Order("id desc").Find(&items).Error
	return items, err
}

// FindByID returns a promo code by id.
func (r *PromoCodeRepository) FindByID(id int64) (*models.PromoCode, error) {
	var p models.PromoCode
	err := r.db.First(&p, id).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &p, nil
}

// FindActiveByCode returns an active promo code by its code.
func (r *PromoCodeRepository) FindActiveByCode(code string) (*models.PromoCode, error) {
	var p models.PromoCode
	err := r.db.Where("code = ? AND is_active = ?", code, true).First(&p).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &p, nil
}

// ExistsByCode reports whether another promo code uses the code.
func (r *PromoCodeRepository) ExistsByCode(code string, exceptID int64) (bool, error) {
	var count int64
	q := r.db.Model(&models.PromoCode{}).Where("code = ?", code)
	if exceptID > 0 {
		q = q.Where("id <> ?", exceptID)
	}
	err := q.Count(&count).Error
	return count > 0, err
}

// UsedByUser reports whether the user already used the code (unused transaction).
func (r *PromoCodeRepository) UsedByUser(promoCodeID, userID int64) (bool, error) {
	var count int64
	err := r.db.Model(&models.UsedPromoCode{}).
		Where("promo_code_id = ? AND user_id = ? AND transaction_id IS NULL", promoCodeID, userID).
		Count(&count).Error
	return count > 0, err
}

// Create inserts a promo code.
func (r *PromoCodeRepository) Create(p *models.PromoCode) error { return r.db.Create(p).Error }

// Update applies field changes.
func (r *PromoCodeRepository) Update(id int64, fields map[string]any) (*models.PromoCode, error) {
	var p models.PromoCode
	if err := r.db.Model(&models.PromoCode{}).Where("id = ?", id).Updates(fields).Error; err != nil {
		return nil, err
	}
	if err := r.db.First(&p, id).Error; err != nil {
		return nil, err
	}
	return &p, nil
}

// Delete soft-deletes a promo code.
func (r *PromoCodeRepository) Delete(id int64) error {
	return r.db.Delete(&models.PromoCode{}, id).Error
}

// DecrementUserCount reduces the remaining usage count.
func (r *PromoCodeRepository) DecrementUserCount(id int64) error {
	return r.db.Model(&models.PromoCode{}).Where("id = ? AND user_count > 0", id).
		UpdateColumn("user_count", gorm.Expr("user_count - 1")).Error
}

// MarkUsed records that the user used the code for an order/transaction.
func (r *PromoCodeRepository) MarkUsed(promoCodeID, userID int64, orderID *int64, transactionID *string) error {
	return r.db.Create(&models.UsedPromoCode{
		PromoCodeID: promoCodeID, UserID: userID, OrderID: orderID, TransactionID: transactionID,
	}).Error
}
