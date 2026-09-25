// Package responses holds PromoCode module API response shapes.
package responses

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/modules/promocode/models"
)

// JSON maps a promo code to its API shape.
func JSON(p models.PromoCode) gin.H {
	return gin.H{
		"id":               p.ID,
		"code":             p.Code,
		"discount_percent": p.DiscountPercent,
		"user_count":       p.UserCount,
		"is_active":        p.IsActive,
		"created_at":       p.CreatedAt,
		"updated_at":       p.UpdatedAt,
	}
}

// Collection maps promo codes to their API shape.
func Collection(items []models.PromoCode) []gin.H {
	out := make([]gin.H, 0, len(items))
	for _, p := range items {
		out = append(out, JSON(p))
	}
	return out
}
