// Package responses holds Balance module API response shapes.
package responses

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/modules/balance/models"
)

// JSON maps a balance row to its API shape.
func JSON(b models.Balance) gin.H {
	return gin.H{
		"id":         b.ID,
		"amount":     b.Amount,
		"type":       b.Type,
		"note":       b.Note,
		"created_at": b.CreatedAt.Format("2006-01-02 15:04:05"),
	}
}

// Collection maps balance rows to their API shape.
func Collection(items []models.Balance) []gin.H {
	out := make([]gin.H, 0, len(items))
	for _, b := range items {
		out = append(out, JSON(b))
	}
	return out
}
