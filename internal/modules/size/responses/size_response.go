// Package responses holds Size module API response shapes.
package responses

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/modules/size/models"
)

// JSON maps a size to its API shape.
func JSON(s models.Size) gin.H {
	return gin.H{
		"id":         s.ID,
		"name":       s.Name,
		"icon":       s.Icon,
		"is_active":  s.IsActive,
		"sort_order": s.SortOrder,
	}
}

// Collection maps sizes to their API shape.
func Collection(items []models.Size) []gin.H {
	out := make([]gin.H, 0, len(items))
	for _, s := range items {
		out = append(out, JSON(s))
	}
	return out
}
