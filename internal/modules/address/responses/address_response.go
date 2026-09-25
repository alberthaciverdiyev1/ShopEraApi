// Package responses holds Address module API response shapes.
package responses

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/modules/address/models"
)

// JSON maps an address to its API shape (city resolved to its display name).
func JSON(a models.Address, cityName string) gin.H {
	city := a.City
	cityKey := a.City
	if cityName != "" {
		city = cityName
	}
	return gin.H{
		"id":                     a.ID,
		"user_id":                a.UserID,
		"city":                   city,
		"city_key":               cityKey,
		"town_village_district":  a.TownVillageDistrict,
		"street_building_number": a.StreetBuildingNumber,
		"unit_floor_apartment":   a.UnitFloorApartment,
		"is_default":             a.IsDefault,
		"full_name":              a.FullName,
		"contact_number":         a.ContactNumber,
		"latitude":               a.Latitude,
		"longitude":              a.Longitude,
		"location_label":         a.LocationLabel,
		"created_at":             a.CreatedAt,
		"updated_at":             a.UpdatedAt,
	}
}
