// Package responses holds Delivery module API response shapes.
package responses

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/modules/delivery/models"
)

// TownJSON maps a town to its API shape.
func TownJSON(t models.CityTown) gin.H {
	return gin.H{"id": t.ID, "name": t.Name}
}

// CityJSON maps a city (with its towns) to its API shape.
func CityJSON(c models.City) gin.H {
	towns := make([]gin.H, 0, len(c.Towns))
	for _, t := range c.Towns {
		towns = append(towns, TownJSON(t))
	}
	return gin.H{
		"id":    c.ID,
		"key":   c.Key,
		"name":  c.Name,
		"towns": towns,
	}
}
