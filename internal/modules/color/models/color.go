// Package models holds the Color GORM model.
package models

import (
	"time"

	"gorm.io/gorm"
)

// Color maps the existing `colors` table.
type Color struct {
	ID        int64          `gorm:"column:id;primaryKey"`
	Name      string         `gorm:"column:name"`
	Hex       *string        `gorm:"column:hex"`
	IsActive  bool           `gorm:"column:is_active;default:true"`
	SortOrder int            `gorm:"column:sort_order;default:0"`
	CreatedAt time.Time      `gorm:"column:created_at"`
	UpdatedAt time.Time      `gorm:"column:updated_at"`
	DeletedAt gorm.DeletedAt `gorm:"column:deleted_at;index"`
}

func (Color) TableName() string { return "colors" }
