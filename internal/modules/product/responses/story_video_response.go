package responses

import (
	"time"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	"shopera/internal/modules/product/models"
)

// StoryVideoJSON maps a story video to its API shape (Laravel ProductStoryVideoResource).
func StoryVideoJSON(v models.ProductVideo) gin.H {
	now := time.Now()

	expiresAt := v.StoryExpiresAt
	expiresOut := v.CreatedAt.Add(24 * time.Hour)
	if expiresAt != nil {
		expiresOut = *expiresAt
	}
	isActive := !v.IsStoryHidden && expiresOut.After(now)

	var product gin.H
	if v.Product != nil {
		var image any
		if len(v.Product.Images) > 0 {
			image = helpers.StorageURL(v.Product.Images[0].ImagePath)
		}
		product = gin.H{
			"id":       v.Product.ID,
			"title":    v.Product.Title,
			"image":    image,
			"price":    v.Product.Price,
			"discount": v.Product.Discount,
		}
	}

	return gin.H{
		"id":              v.ID,
		"video_path":      helpers.StorageURL(v.VideoPath),
		"created_at":      v.CreatedAt.Format("2006-01-02 15:04:05"),
		"expires_at":      expiresOut.Format("2006-01-02 15:04:05"),
		"is_story_hidden": v.IsStoryHidden,
		"is_story_active": isActive,
		"product":         product,
	}
}

// StoryVideoCollection maps story videos to their API shape.
func StoryVideoCollection(items []models.ProductVideo) []gin.H {
	out := make([]gin.H, 0, len(items))
	for _, v := range items {
		out = append(out, StoryVideoJSON(v))
	}
	return out
}
