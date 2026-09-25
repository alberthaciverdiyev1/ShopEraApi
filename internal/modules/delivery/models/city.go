// Package models holds the Delivery/location GORM models.
package models

import (
	"time"

	"gorm.io/gorm"
)

// City maps the `cities` table.
type City struct {
	ID        int64          `gorm:"column:id;primaryKey"`
	Key       string         `gorm:"column:key"`
	Name      string         `gorm:"column:name"`
	IsActive  bool           `gorm:"column:is_active;default:true"`
	CreatedAt time.Time      `gorm:"column:created_at"`
	UpdatedAt time.Time      `gorm:"column:updated_at"`
	DeletedAt gorm.DeletedAt `gorm:"column:deleted_at;index"`

	Towns []CityTown `gorm:"foreignKey:CityID"`
}

func (City) TableName() string { return "cities" }
