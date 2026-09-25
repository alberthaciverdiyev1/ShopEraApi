package models

import "time"

// Message maps the `messages` table.
type Message struct {
	ID             int64     `gorm:"column:id;primaryKey"`
	ConversationID int64     `gorm:"column:conversation_id"`
	SenderType     string    `gorm:"column:sender_type"`
	SenderID       int64     `gorm:"column:sender_id"`
	Message        *string   `gorm:"column:message"`
	IsRead         bool      `gorm:"column:is_read;default:false"`
	CreatedAt      time.Time `gorm:"column:created_at"`
	UpdatedAt      time.Time `gorm:"column:updated_at"`

	Attachments []MessageAttachment `gorm:"foreignKey:MessageID"`
}

func (Message) TableName() string { return "messages" }
