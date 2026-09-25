// Package responses holds Popup module API response shapes.
package responses

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	"shopera/internal/modules/popup/models"
)

// JSON maps a popup to its API shape. image falls back to the video URL.
func JSON(p models.Popup) gin.H {
	var image string
	if p.Image != nil {
		image = helpers.StorageURL(*p.Image)
	} else if p.Video != nil {
		image = helpers.StorageURL(*p.Video)
	}

	var video any
	if p.Video != nil {
		video = helpers.StorageURL(*p.Video)
	}

	return gin.H{
		"id":                p.ID,
		"image":             image,
		"video":             video,
		"show_on_home_page": p.ShowOnHomePage,
	}
}

// Collection maps popups to their API shape.
func Collection(items []models.Popup) []gin.H {
	out := make([]gin.H, 0, len(items))
	for _, p := range items {
		out = append(out, JSON(p))
	}
	return out
}
