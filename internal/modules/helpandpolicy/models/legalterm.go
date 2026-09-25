package models

import (
	"time"

	"gorm.io/gorm"
)

// LegalTerm maps the `legal_terms_policies` table (translatable jsonb html).
type LegalTerm struct {
	ID        int64             `gorm:"column:id;primaryKey"`
	Type      string            `gorm:"column:type"`
	HTML      map[string]string `gorm:"column:html;serializer:json"`
	CreatedAt time.Time         `gorm:"column:created_at"`
	UpdatedAt time.Time         `gorm:"column:updated_at"`
	DeletedAt gorm.DeletedAt    `gorm:"column:deleted_at;index"`
}

func (LegalTerm) TableName() string { return "legal_terms_policies" }
