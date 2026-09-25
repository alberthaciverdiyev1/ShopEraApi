// Package responses holds Review module API response shapes.
package responses

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	productresponses "shopera/internal/modules/product/responses"
	"shopera/internal/modules/review/models"
)

func userJSON(review models.Review) gin.H {
	if review.User == nil {
		return gin.H{"id": nil, "name": nil, "email": nil}
	}
	return gin.H{"id": review.User.ID, "name": review.User.Name, "email": review.User.Email}
}

func imageURL(image *string) any {
	if image == nil {
		return nil
	}
	return helpers.StorageURL(*image)
}

// JSON maps a review to its public API shape.
func JSON(review models.Review) gin.H {
	return gin.H{
		"id":         review.ID,
		"rate":       review.Rate,
		"comment":    review.Comment,
		"image":      imageURL(review.Image),
		"user":       userJSON(review),
		"product_id": review.ProductID,
		"created_at": review.CreatedAt.Format("02.01.2006 15:04"),
	}
}

// Collection maps reviews to their public API shape.
func Collection(items []models.Review) []gin.H {
	out := make([]gin.H, 0, len(items))
	for _, r := range items {
		out = append(out, JSON(r))
	}
	return out
}

// ListJSON maps a review to its admin API shape.
func ListJSON(review models.Review) gin.H {
	out := JSON(review)
	out["status_name"] = models.StatusLabel(review.Status)
	out["status_code"] = models.StatusName(review.Status)
	if review.Product != nil {
		out["product"] = productresponses.JSON(*review.Product, "az", nil)
	} else {
		out["product"] = nil
	}
	return out
}

// ListCollection maps reviews to their admin API shape.
func ListCollection(items []models.Review) []gin.H {
	out := make([]gin.H, 0, len(items))
	for _, r := range items {
		out = append(out, ListJSON(r))
	}
	return out
}
