package models

import (
	"time"

	"gorm.io/gorm"
)

// PickupPoint maps the `pickup_points` table (Starex columns excluded).
type PickupPoint struct {
	ID           int64             `gorm:"column:id;primaryKey"`
	Name         string            `gorm:"column:name"`
	Address      string            `gorm:"column:address"`
	Price        *float64          `gorm:"column:price"`
	DeliveryTime map[string]string `gorm:"column:delivery_time;serializer:json"`
	IsActive     bool              `gorm:"column:is_active;default:true"`
	CreatedAt    time.Time         `gorm:"column:created_at"`
	UpdatedAt    time.Time         `gorm:"column:updated_at"`
	DeletedAt    gorm.DeletedAt    `gorm:"column:deleted_at;index"`
}

func (PickupPoint) TableName() string { return "pickup_points" }
