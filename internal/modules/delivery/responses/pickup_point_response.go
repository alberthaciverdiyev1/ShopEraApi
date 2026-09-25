package responses

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/modules/delivery/models"
)

// PickupPointJSON maps a pickup point to its API shape.
func PickupPointJSON(p models.PickupPoint, admin bool) gin.H {
	out := gin.H{
		"id":            p.ID,
		"name":          p.Name,
		"address":       p.Address,
		"price":         p.Price,
		"delivery_time": p.DeliveryTime,
		"is_active":     p.IsActive,
		"created_at":    p.CreatedAt.Format("2006-01-02 15:04:05"),
	}
	if admin {
		out["is_active"] = p.IsActive
	}
	return out
}

// PickupPointCollection maps pickup points to their API shape.
func PickupPointCollection(items []models.PickupPoint) []gin.H {
	out := make([]gin.H, 0, len(items))
	for _, p := range items {
		out = append(out, PickupPointJSON(p, false))
	}
	return out
}
