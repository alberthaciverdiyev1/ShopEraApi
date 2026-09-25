package helpers

import (
	"time"

	"shopera/internal/modules/product/models"
)

// RetailPriceInfo is the retail pricing result.
type RetailPriceInfo struct {
	Original   float64
	Discounted float64
	Final      float64
}

// Retail computes the retail price for a product (optionally a specific size).
// Size pivot price/discount win over the product's own values; the minimum pivot
// value is the last fallback (Laravel ProductPricingService::retailPrices).
func Retail(p models.Product, pivots []models.ProductSize, sizeID *int64) RetailPriceInfo {
	var selected *models.ProductSize
	if sizeID != nil {
		for i := range pivots {
			if pivots[i].SizeID == *sizeID {
				selected = &pivots[i]
				break
			}
		}
	}

	original := 0.0
	switch {
	case selected != nil && selected.Price != nil:
		original = *selected.Price
	case p.Price != nil:
		original = *p.Price
	case minPivot(pivots, func(s models.ProductSize) *float64 { return s.Price }) != nil:
		original = *minPivot(pivots, func(s models.ProductSize) *float64 { return s.Price })
	}

	var discount *float64
	switch {
	case selected != nil && selected.Discount != nil:
		discount = selected.Discount
	case p.Discount != nil:
		discount = p.Discount
	case sizeID == nil:
		discount = minPivot(pivots, func(s models.ProductSize) *float64 { return s.Discount })
	}

	discounted := 0.0
	if discount != nil {
		discounted = *discount
	}

	valid := discounted > 0 && discounted < original && activeDiscount(p)
	if valid {
		return RetailPriceInfo{Original: original, Discounted: discounted, Final: discounted}
	}
	return RetailPriceInfo{Original: original, Discounted: 0, Final: original}
}

func activeDiscount(p models.Product) bool {
	return p.DiscountExpireDate == nil || p.DiscountExpireDate.After(time.Now())
}

func minPivot(pivots []models.ProductSize, pick func(models.ProductSize) *float64) *float64 {
	var min *float64
	for _, s := range pivots {
		v := pick(s)
		if v == nil {
			continue
		}
		if min == nil || *v < *min {
			min = v
		}
	}
	return min
}
