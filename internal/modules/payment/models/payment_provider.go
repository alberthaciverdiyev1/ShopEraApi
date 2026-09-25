// Package models holds the Payment GORM models.
package models

import (
	"time"

	"gorm.io/gorm"
)

// PaymentProvider is a selectable/configured payment provider.
type PaymentProvider struct {
	ID        int64             `gorm:"column:id;primaryKey"`
	Key       string            `gorm:"column:key"`
	Name      string            `gorm:"column:name"`
	IsActive  bool              `gorm:"column:is_active;default:true"`
	SortOrder int               `gorm:"column:sort_order;default:0"`
	Config    map[string]string `gorm:"column:config;serializer:json"`
	CreatedAt time.Time         `gorm:"column:created_at"`
	UpdatedAt time.Time         `gorm:"column:updated_at"`
	DeletedAt gorm.DeletedAt    `gorm:"column:deleted_at;index"`
}

func (PaymentProvider) TableName() string { return "payment_providers" }
