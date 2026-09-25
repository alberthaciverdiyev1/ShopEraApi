// Package responses holds Category module API response shapes.
package responses

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	"shopera/internal/modules/category/models"
)

// JSON maps a category to its API shape (name localized to lang).
func JSON(c models.Category, lang string) gin.H {
	return gin.H{
		"id":          c.ID,
		"name":        helpers.Trans(c.Name, lang),
		"image":       c.Image,
		"description": c.Description,
		"parent_id":   c.ParentID,
		"is_active":   c.IsActive,
		"sort_order":  c.SortOrder,
	}
}

// Collection maps categories to their API shape.
func Collection(items []models.Category, lang string) []gin.H {
	out := make([]gin.H, 0, len(items))
	for _, c := range items {
		out = append(out, JSON(c, lang))
	}
	return out
}
