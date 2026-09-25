// Package requests holds Address module request payloads.
package requests

// SaveRequest is used for both create and update.
type SaveRequest struct {
	City                 string   `json:"city" binding:"required,max=255"`
	TownVillageDistrict  string   `json:"town_village_district" binding:"required,max=255"`
	StreetBuildingNumber string   `json:"street_building_number" binding:"required,max=255"`
	UnitFloorApartment   string   `json:"unit_floor_apartment" binding:"required,max=255"`
	FullName             string   `json:"full_name" binding:"required,max=255"`
	ContactNumber        string   `json:"contact_number" binding:"required"`
	IsDefault            *bool    `json:"is_default"`
	Latitude             *float64 `json:"latitude" binding:"omitempty,gte=-90,lte=90"`
	Longitude            *float64 `json:"longitude" binding:"omitempty,gte=-180,lte=180"`
	LocationLabel        *string  `json:"location_label" binding:"omitempty,max=255"`
}
