// Package responses holds Brand module API response shapes.
package responses

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/modules/brand/models"
)

// JSON maps a brand to its API shape.
func JSON(b models.Brand) gin.H {
	return gin.H{
		"id":         b.ID,
		"name":       b.Name,
		"image":      b.Image,
		"is_active":  b.IsActive,
		"sort_order": b.SortOrder,
	}
}

// Collection maps brands to their API shape.
func Collection(items []models.Brand) []gin.H {
	out := make([]gin.H, 0, len(items))
	for _, b := range items {
		out = append(out, JSON(b))
	}
	return out
}

// Page mirrors a Laravel paginator payload.
func Page(items []models.Brand, total int64, page, perPage int) gin.H {
	lastPage := int((total + int64(perPage) - 1) / int64(perPage))
	return gin.H{
		"data": Collection(items),
		"meta": gin.H{
			"current_page": page,
			"per_page":     perPage,
			"total":        total,
			"last_page":    lastPage,
		},
	}
}
