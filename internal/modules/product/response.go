package product

import "github.com/gin-gonic/gin"

// JSON maps a product to its API shape (localized title).
func JSON(p Product, lang string) gin.H {
	return gin.H{
		"id":          p.ID,
		"title":       Trans(p.Title, lang),
		"price":       p.Price,
		"stock_count": p.StockCount,
		"is_active":   p.IsActive,
	}
}

// Collection maps products to their API shape.
func Collection(products []Product, lang string) []gin.H {
	items := make([]gin.H, 0, len(products))
	for _, p := range products {
		items = append(items, JSON(p, lang))
	}
	return items
}

// ListResult is the paginated list payload.
type ListResult struct {
	Data []map[string]any `json:"data"`
	Meta map[string]any   `json:"meta"`
}
