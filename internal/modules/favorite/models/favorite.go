// Package models holds the Favorite GORM model (user_favorites pivot).
package models

import "time"

// Favorite maps the `user_favorites` table.
type Favorite struct {
	ID        int64     `gorm:"column:id;primaryKey"`
	UserID    int64     `gorm:"column:user_id"`
	ProductID int64     `gorm:"column:product_id"`
	CreatedAt time.Time `gorm:"column:created_at"`
	UpdatedAt time.Time `gorm:"column:updated_at"`
}

func (Favorite) TableName() string { return "user_favorites" }
