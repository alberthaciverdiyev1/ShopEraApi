package models

import "time"

// RefreshToken maps the `refresh_tokens` table (hashed tokens, revocable).
type RefreshToken struct {
	ID        int64      `gorm:"column:id;primaryKey"`
	UserID    int64      `gorm:"column:user_id"`
	Token     string     `gorm:"column:token"`
	ExpiresAt time.Time  `gorm:"column:expires_at"`
	RevokedAt *time.Time `gorm:"column:revoked_at"`
	CreatedAt time.Time  `gorm:"column:created_at"`
	UpdatedAt time.Time  `gorm:"column:updated_at"`
}

func (RefreshToken) TableName() string { return "refresh_tokens" }
