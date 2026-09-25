// Package models holds the HelpAndPolicy GORM models.
package models

import (
	"time"

	"gorm.io/gorm"
)

// Faq maps the existing `faqs` table (translatable jsonb title/description).
type Faq struct {
	ID          int64             `gorm:"column:id;primaryKey"`
	Title       map[string]string `gorm:"column:title;serializer:json"`
	Description map[string]string `gorm:"column:description;serializer:json"`
	Type        string            `gorm:"column:type"`
	CreatedAt   time.Time         `gorm:"column:created_at"`
	UpdatedAt   time.Time         `gorm:"column:updated_at"`
	DeletedAt   gorm.DeletedAt    `gorm:"column:deleted_at;index"`
}

func (Faq) TableName() string { return "faqs" }
