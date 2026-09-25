// Package responses holds Product module API response shapes.
package responses

import (
	"github.com/gin-gonic/gin"

	producthelpers "shopera/internal/modules/product/helpers"
	"shopera/internal/modules/product/models"
)

// JSON maps a product to its API shape (localized title).
func JSON(p models.Product, lang string) gin.H {
	return gin.H{
		"id":          p.ID,
		"title":       producthelpers.Trans(p.Title, lang),
		"price":       p.Price,
		"stock_count": p.StockCount,
		"is_active":   p.IsActive,
	}
}

// ListResult is the paginated list payload.
type ListResult struct {
	Data []map[string]any `json:"data"`
	Meta map[string]any   `json:"meta"`
}
