// Package models holds the PromoCode GORM models.
package models

import (
	"time"

	"gorm.io/gorm"
)

// PromoCode maps the `promo_codes` table.
type PromoCode struct {
	ID              int64          `gorm:"column:id;primaryKey" json:"id"`
	Code            string         `gorm:"column:code" json:"code"`
	DiscountPercent *float64       `gorm:"column:discount_percent" json:"discount_percent"`
	UserCount       int            `gorm:"column:user_count" json:"user_count"`
	IsActive        bool           `gorm:"column:is_active;default:true" json:"is_active"`
	CreatedAt       time.Time      `gorm:"column:created_at" json:"created_at"`
	UpdatedAt       time.Time      `gorm:"column:updated_at" json:"updated_at"`
	DeletedAt       gorm.DeletedAt `gorm:"column:deleted_at;index" json:"-"`
}

func (PromoCode) TableName() string { return "promo_codes" }
