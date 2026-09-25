// Package models holds the Size GORM model.
package models

import (
	"time"

	"gorm.io/gorm"
)

// Size maps the existing `sizes` table.
type Size struct {
	ID        int64          `gorm:"column:id;primaryKey"`
	Name      string         `gorm:"column:name"`
	Icon      *string        `gorm:"column:icon"`
	IsActive  bool           `gorm:"column:is_active;default:true"`
	SortOrder int            `gorm:"column:sort_order;default:0"`
	CreatedAt time.Time      `gorm:"column:created_at"`
	UpdatedAt time.Time      `gorm:"column:updated_at"`
	DeletedAt gorm.DeletedAt `gorm:"column:deleted_at;index"`
}

func (Size) TableName() string { return "sizes" }
