package models

import "time"

// DeliveryInfo maps the `delivery_infos` table.
type DeliveryInfo struct {
	ID          int64             `gorm:"column:id;primaryKey"`
	Type        string            `gorm:"column:type"`
	Description map[string]string `gorm:"column:description;serializer:json"`
	CreatedAt   time.Time         `gorm:"column:created_at"`
	UpdatedAt   time.Time         `gorm:"column:updated_at"`
}

func (DeliveryInfo) TableName() string { return "delivery_infos" }
