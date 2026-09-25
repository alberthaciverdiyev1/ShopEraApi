// Package models holds the Notification GORM models.
package models

import (
	"time"
)

// Notification maps the `notifications` table.
type Notification struct {
	ID        int64          `gorm:"column:id;primaryKey" json:"id"`
	Title     string         `gorm:"column:title" json:"title"`
	Body      string         `gorm:"column:body" json:"body"`
	UserID    *int64         `gorm:"column:user_id" json:"user_id"`
	All       bool           `gorm:"column:all" json:"all"`
	Data      map[string]any `gorm:"column:data;serializer:json" json:"data"`
	Icon      *string        `gorm:"column:icon" json:"icon"`
	Image     *string        `gorm:"column:image" json:"image"`
	URL       *string        `gorm:"column:url" json:"url"`
	Source    string         `gorm:"column:source" json:"source"`
	CreatedAt time.Time      `gorm:"column:created_at" json:"created_at"`
	UpdatedAt time.Time      `gorm:"column:updated_at" json:"updated_at"`
}

func (Notification) TableName() string { return "notifications" }
