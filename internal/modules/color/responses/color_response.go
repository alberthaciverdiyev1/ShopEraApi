// Package responses holds Color module API response shapes.
package responses

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/modules/color/models"
)

// JSON maps a color to its API shape.
func JSON(c models.Color) gin.H {
	return gin.H{
		"id":         c.ID,
		"name":       c.Name,
		"hex":        c.Hex,
		"is_active":  c.IsActive,
		"sort_order": c.SortOrder,
	}
}

// Collection maps colors to their API shape.
func Collection(items []models.Color) []gin.H {
	out := make([]gin.H, 0, len(items))
	for _, c := range items {
		out = append(out, JSON(c))
	}
	return out
}
