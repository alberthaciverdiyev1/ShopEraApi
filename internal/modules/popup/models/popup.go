// Package models holds the Popup GORM model.
package models

import (
	"time"

	"gorm.io/gorm"
)

// Popup maps the existing `popups` table.
type Popup struct {
	ID             int64          `gorm:"column:id;primaryKey"`
	Image          *string        `gorm:"column:image"`
	Video          *string        `gorm:"column:video"`
	ShowOnHomePage bool           `gorm:"column:show_on_home_page"`
	CreatedAt      time.Time      `gorm:"column:created_at"`
	UpdatedAt      time.Time      `gorm:"column:updated_at"`
	DeletedAt      gorm.DeletedAt `gorm:"column:deleted_at;index"`
}

func (Popup) TableName() string { return "popups" }
