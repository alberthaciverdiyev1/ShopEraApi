package models

import "time"

// OrderStatusRow maps the `order_statuses` history table.
type OrderStatusRow struct {
	ID        int64     `gorm:"column:id;primaryKey"`
	OrderID   int64     `gorm:"column:order_id"`
	Status    int       `gorm:"column:status"`
	CreatedAt time.Time `gorm:"column:created_at"`
	UpdatedAt time.Time `gorm:"column:updated_at"`
}

func (OrderStatusRow) TableName() string { return "order_statuses" }

// Value returns the status enum.
func (r OrderStatusRow) Value() OrderStatus { return OrderStatus(r.Status) }
