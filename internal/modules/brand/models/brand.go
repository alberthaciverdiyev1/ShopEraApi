// Package models holds the Brand GORM model.
package models

import (
	"time"

	"gorm.io/gorm"
)

// Brand maps the existing `brands` table.
type Brand struct {
	ID        int64          `gorm:"column:id;primaryKey"`
	Name      string         `gorm:"column:name"`
	Image     *string        `gorm:"column:image"`
	IsActive  bool           `gorm:"column:is_active;default:true"`
	SortOrder int            `gorm:"column:sort_order;default:0"`
	CreatedAt time.Time      `gorm:"column:created_at"`
	UpdatedAt time.Time      `gorm:"column:updated_at"`
	DeletedAt gorm.DeletedAt `gorm:"column:deleted_at;index"`
}

func (Brand) TableName() string { return "brands" }
