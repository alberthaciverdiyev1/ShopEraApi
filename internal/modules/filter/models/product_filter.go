package models

import "time"

// ProductFilter is a product's value for a filter.
type ProductFilter struct {
	ID        int64     `gorm:"column:id;primaryKey"`
	ProductID int64     `gorm:"column:product_id"`
	FilterID  int64     `gorm:"column:filter_id"`
	Value     *string   `gorm:"column:value"`
	CreatedAt time.Time `gorm:"column:created_at"`
	UpdatedAt time.Time `gorm:"column:updated_at"`
}

func (ProductFilter) TableName() string { return "product_filters" }
