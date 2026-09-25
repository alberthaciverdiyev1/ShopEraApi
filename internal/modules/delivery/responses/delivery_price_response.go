package responses

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/modules/delivery/models"
)

// DeliveryPriceJSON maps a delivery price to its API shape.
func DeliveryPriceJSON(d models.DeliveryPrice) gin.H {
	return gin.H{
		"id":                 d.ID,
		"city_name":          d.CityName,
		"price":              d.Price,
		"fast_price":         d.FastPrice,
		"free_from":          d.FreeFrom,
		"delivery_time":      d.DeliveryTime,
		"fast_delivery_time": d.FastDeliveryTime,
		"is_active":          d.IsActive,
	}
}

// DeliveryPriceCollection maps delivery prices to their API shape.
func DeliveryPriceCollection(items []models.DeliveryPrice) []gin.H {
	out := make([]gin.H, 0, len(items))
	for _, d := range items {
		out = append(out, DeliveryPriceJSON(d))
	}
	return out
}
