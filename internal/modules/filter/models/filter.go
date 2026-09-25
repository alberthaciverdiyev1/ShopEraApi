// Package models holds the Filter module GORM models.
package models

import "time"

// Filter maps the `filters` table (dynamic, category-scoped filters).
type Filter struct {
	ID        int64             `gorm:"column:id;primaryKey"`
	Title     map[string]string `gorm:"column:title;serializer:json"`
	Type      string            `gorm:"column:type"`
	Options   []string          `gorm:"column:options;serializer:json"`
	CreatedAt time.Time         `gorm:"column:created_at"`
	UpdatedAt time.Time         `gorm:"column:updated_at"`
}

func (Filter) TableName() string { return "filters" }
