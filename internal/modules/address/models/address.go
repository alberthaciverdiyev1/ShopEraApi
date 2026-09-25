// Package models holds the Address GORM model.
package models

import (
	"time"

	"gorm.io/gorm"
)

// Address maps the `user_addresses` table.
type Address struct {
	ID                   int64          `gorm:"column:id;primaryKey"`
	UserID               int64          `gorm:"column:user_id"`
	City                 string         `gorm:"column:city"`
	TownVillageDistrict  string         `gorm:"column:town_village_district"`
	StreetBuildingNumber string         `gorm:"column:street_building_number"`
	UnitFloorApartment   string         `gorm:"column:unit_floor_apartment"`
	IsDefault            bool           `gorm:"column:is_default"`
	FullName             string         `gorm:"column:full_name"`
	ContactNumber        string         `gorm:"column:contact_number"`
	Latitude             *float64       `gorm:"column:latitude"`
	Longitude            *float64       `gorm:"column:longitude"`
	LocationLabel        *string        `gorm:"column:location_label"`
	CreatedAt            time.Time      `gorm:"column:created_at"`
	UpdatedAt            time.Time      `gorm:"column:updated_at"`
	DeletedAt            gorm.DeletedAt `gorm:"column:deleted_at;index"`
}

func (Address) TableName() string { return "user_addresses" }
