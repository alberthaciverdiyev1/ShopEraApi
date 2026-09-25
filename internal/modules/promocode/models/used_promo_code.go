package models

import (
	"time"

	"gorm.io/gorm"
)

// UsedPromoCode maps the `used_promo_codes` pivot table.
type UsedPromoCode struct {
	ID            int64          `gorm:"column:id;primaryKey"`
	PromoCodeID   int64          `gorm:"column:promo_code_id"`
	UserID        int64          `gorm:"column:user_id"`
	OrderID       *int64         `gorm:"column:order_id"`
	TransactionID *string        `gorm:"column:transaction_id"`
	CreatedAt     time.Time      `gorm:"column:created_at"`
	UpdatedAt     time.Time      `gorm:"column:updated_at"`
	DeletedAt     gorm.DeletedAt `gorm:"column:deleted_at;index"`
}

func (UsedPromoCode) TableName() string { return "used_promo_codes" }
