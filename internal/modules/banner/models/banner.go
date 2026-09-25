// Package models holds the Banner GORM model.
package models

import (
	"time"

	"gorm.io/gorm"
)

// Banner maps the existing `banners` table.
type Banner struct {
	ID          int64          `gorm:"column:id;primaryKey"`
	Image       string         `gorm:"column:image"`
	SecondImage *string        `gorm:"column:second_image"`
	Type        string         `gorm:"column:type"`
	URL         *string        `gorm:"column:url"`
	CreatedAt   time.Time      `gorm:"column:created_at"`
	UpdatedAt   time.Time      `gorm:"column:updated_at"`
	DeletedAt   gorm.DeletedAt `gorm:"column:deleted_at;index"`
}

func (Banner) TableName() string { return "banners" }
