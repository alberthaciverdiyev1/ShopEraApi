// Package models holds the Chat GORM models.
package models

import "time"

// Conversation maps the `conversations` table.
type Conversation struct {
	ID            int64      `gorm:"column:id;primaryKey"`
	UserID        int64      `gorm:"column:user_id"`
	AdminID       int64      `gorm:"column:admin_id"`
	LastMessageAt *time.Time `gorm:"column:last_message_at"`
	CreatedAt     time.Time  `gorm:"column:created_at"`
	UpdatedAt     time.Time  `gorm:"column:updated_at"`
}

func (Conversation) TableName() string { return "conversations" }
