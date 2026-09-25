// Package responses holds Banner module API response shapes.
package responses

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	"shopera/internal/modules/banner/models"
)

// JSON maps a banner to its API shape (images as public URLs).
func JSON(b models.Banner) gin.H {
	var second any
	if b.SecondImage != nil {
		second = helpers.StorageURL(*b.SecondImage)
	}
	return gin.H{
		"id":           b.ID,
		"image":        helpers.StorageURL(b.Image),
		"second_image": second,
		"type":         b.Type,
		"url":          b.URL,
	}
}

// Collection maps banners to their API shape.
func Collection(items []models.Banner) []gin.H {
	out := make([]gin.H, 0, len(items))
	for _, b := range items {
		out = append(out, JSON(b))
	}
	return out
}
