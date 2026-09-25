package models

import (
	"time"

	"gorm.io/gorm"
)

// AutoReply maps the `auto_replies` table (translatable jsonb question/answer).
type AutoReply struct {
	ID        int64             `gorm:"column:id;primaryKey"`
	Question  map[string]string `gorm:"column:question;serializer:json"`
	Answer    map[string]string `gorm:"column:answer;serializer:json"`
	CreatedAt time.Time         `gorm:"column:created_at"`
	UpdatedAt time.Time         `gorm:"column:updated_at"`
	DeletedAt gorm.DeletedAt    `gorm:"column:deleted_at;index"`
}

func (AutoReply) TableName() string { return "auto_replies" }
