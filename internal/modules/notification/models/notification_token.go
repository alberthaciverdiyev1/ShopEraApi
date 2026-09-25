package models

import "time"

// NotificationToken maps the `notification_tokens` table (guests allowed).
type NotificationToken struct {
	ID         int64      `gorm:"column:id;primaryKey"`
	UserID     *int64     `gorm:"column:user_id"`
	Token      string     `gorm:"column:token"`
	DeviceType string     `gorm:"column:device_type"`
	IsActive   bool       `gorm:"column:is_active;default:true"`
	LastUsedAt *time.Time `gorm:"column:last_used_at"`
	CreatedAt  time.Time  `gorm:"column:created_at"`
	UpdatedAt  time.Time  `gorm:"column:updated_at"`
}

func (NotificationToken) TableName() string { return "notification_tokens" }
