// Package responses holds Notification module API response shapes.
package responses

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/modules/notification/models"
)

// JSON maps a notification to its API shape.
func JSON(n models.Notification) gin.H {
	return gin.H{
		"id":         n.ID,
		"title":      n.Title,
		"body":       n.Body,
		"user_id":    n.UserID,
		"all":        n.All,
		"data":       n.Data,
		"icon":       n.Icon,
		"image":      n.Image,
		"url":        n.URL,
		"source":     n.Source,
		"created_at": n.CreatedAt,
	}
}

// Collection maps notifications to their API shape.
func Collection(items []models.Notification) []gin.H {
	out := make([]gin.H, 0, len(items))
	for _, n := range items {
		out = append(out, JSON(n))
	}
	return out
}
