// Package product owns the Product model, data access and endpoints.
package product

import "gorm.io/gorm"

// Product maps the existing `products` table (subset of columns).
type Product struct {
	ID         int64             `gorm:"column:id;primaryKey"`
	Title      map[string]string `gorm:"column:title;serializer:json"`
	Price      *float64          `gorm:"column:price"`
	StockCount int               `gorm:"column:stock_count"`
	IsActive   bool              `gorm:"column:is_active"`
	StoreID    *int64            `gorm:"column:store_id"`
	DeletedAt  gorm.DeletedAt    `gorm:"column:deleted_at;index"`
}

func (Product) TableName() string { return "products" }
