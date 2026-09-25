package models

import (
	"time"

	"gorm.io/gorm"
)

// CityTown maps the `city_towns` table (a city's towns / kasaba).
type CityTown struct {
	ID        int64          `gorm:"column:id;primaryKey"`
	CityID    int64          `gorm:"column:city_id"`
	Name      string         `gorm:"column:name"`
	IsActive  bool           `gorm:"column:is_active;default:true"`
	CreatedAt time.Time      `gorm:"column:created_at"`
	UpdatedAt time.Time      `gorm:"column:updated_at"`
	DeletedAt gorm.DeletedAt `gorm:"column:deleted_at;index"`
}

func (CityTown) TableName() string { return "city_towns" }
