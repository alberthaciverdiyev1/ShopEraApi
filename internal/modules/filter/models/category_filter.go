package models

import "time"

// CategoryFilter links a filter to a category.
type CategoryFilter struct {
	ID         int64     `gorm:"column:id;primaryKey"`
	FilterID   int64     `gorm:"column:filter_id"`
	CategoryID int64     `gorm:"column:category_id"`
	CreatedAt  time.Time `gorm:"column:created_at"`
	UpdatedAt  time.Time `gorm:"column:updated_at"`
}

func (CategoryFilter) TableName() string { return "category_filters" }
