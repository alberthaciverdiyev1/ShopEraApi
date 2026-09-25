package responses

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/modules/delivery/models"
)

// DeliveryInfoJSON maps a delivery info to its API shape.
func DeliveryInfoJSON(d models.DeliveryInfo) gin.H {
	return gin.H{"id": d.ID, "type": d.Type, "description": d.Description}
}

// DeliveryInfoCollection maps delivery info rows to their API shape.
func DeliveryInfoCollection(items []models.DeliveryInfo) []gin.H {
	out := make([]gin.H, 0, len(items))
	for _, d := range items {
		out = append(out, DeliveryInfoJSON(d))
	}
	return out
}
