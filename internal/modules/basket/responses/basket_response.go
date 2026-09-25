// Package responses holds Basket module API response shapes.
package responses

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/modules/basket/models"
	producthelpers "shopera/internal/modules/product/helpers"
	productmodels "shopera/internal/modules/product/models"
	productresponses "shopera/internal/modules/product/responses"
)

// JSON maps a basket item to its API shape, pricing its product by quantity.
func JSON(b models.Basket, lang string, pivots []productmodels.ProductSize) gin.H {
	out := gin.H{
		"id":         b.ID,
		"user_id":    b.UserID,
		"product_id": b.ProductID,
		"quantity":   b.Quantity,
		"color_id":   b.ColorID,
		"size_id":    b.SizeID,
		"gender":     b.Gender,
		"selected":   b.Selected,
		"created_at": b.CreatedAt,
		"updated_at": b.UpdatedAt,
	}

	if b.Product != nil {
		price := producthelpers.Retail(*b.Product, pivots, b.SizeID)
		out["retail_unit_price"] = round2(price.Final)
		out["retail_total"] = round2(price.Final * float64(b.Quantity))
		out["product"] = productresponses.JSON(*b.Product, lang, pivots)
	} else {
		out["retail_unit_price"] = nil
		out["retail_total"] = nil
		out["product"] = nil
	}
	return out
}

// Collection maps basket items to their API shape.
func Collection(items []models.Basket, lang string, pivotsByProduct map[int64][]productmodels.ProductSize) []gin.H {
	out := make([]gin.H, 0, len(items))
	for _, b := range items {
		out = append(out, JSON(b, lang, pivotsByProduct[b.ProductID]))
	}
	return out
}

func round2(v float64) float64 {
	return float64(int64(v*100+0.5)) / 100
}
