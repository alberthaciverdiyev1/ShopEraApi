package models

import (
	"time"

	"gorm.io/gorm"
)

// DeliveryPrice maps the `delivery_prices` table (Starex columns excluded).
type DeliveryPrice struct {
	ID               int64          `gorm:"column:id;primaryKey"`
	CityName         string         `gorm:"column:city_name"`
	Price            *float64       `gorm:"column:price"`
	FastPrice        *float64       `gorm:"column:fast_price"`
	FreeFrom         *float64       `gorm:"column:free_from"`
	DeliveryTime     *string        `gorm:"column:delivery_time"`
	FastDeliveryTime *string        `gorm:"column:fast_delivery_time"`
	IsActive         bool           `gorm:"column:is_active;default:true"`
	CreatedAt        time.Time      `gorm:"column:created_at"`
	UpdatedAt        time.Time      `gorm:"column:updated_at"`
	DeletedAt        gorm.DeletedAt `gorm:"column:deleted_at;index"`
}

func (DeliveryPrice) TableName() string { return "delivery_prices" }
