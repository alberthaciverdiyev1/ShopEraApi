// Package models holds the Category GORM model.
package models

import (
	"time"

	"gorm.io/gorm"
)

// Category maps the existing `categories` table.
type Category struct {
	ID          int64             `gorm:"column:id;primaryKey"`
	Name        map[string]string `gorm:"column:name;serializer:json"`
	Image       *string           `gorm:"column:image"`
	Description *string           `gorm:"column:description"`
	ParentID    *int64            `gorm:"column:parent_id"`
	IsActive    bool              `gorm:"column:is_active;default:true"`
	SortOrder   int               `gorm:"column:sort_order;default:0"`
	CreatedAt   time.Time         `gorm:"column:created_at"`
	UpdatedAt   time.Time         `gorm:"column:updated_at"`
	DeletedAt   gorm.DeletedAt    `gorm:"column:deleted_at;index"`
}

func (Category) TableName() string { return "categories" }
