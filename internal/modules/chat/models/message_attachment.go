package models

import "time"

// MessageAttachment maps the `message_attachments` table.
type MessageAttachment struct {
	ID        int64     `gorm:"column:id;primaryKey"`
	MessageID int64     `gorm:"column:message_id"`
	Path      string    `gorm:"column:path"`
	CreatedAt time.Time `gorm:"column:created_at"`
	UpdatedAt time.Time `gorm:"column:updated_at"`
}

func (MessageAttachment) TableName() string { return "message_attachments" }
