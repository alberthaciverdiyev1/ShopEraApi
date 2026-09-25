// Package responses holds Product module API response shapes.
package responses

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	producthelpers "shopera/internal/modules/product/helpers"
	"shopera/internal/modules/product/models"
)

// JSON maps a product to its API shape (localized fields + relations + retail pricing).
func JSON(p models.Product, lang string, pivots []models.ProductSize) gin.H {
	pricing := producthelpers.Retail(p, pivots, nil)

	out := gin.H{
		"id":                   p.ID,
		"title":                producthelpers.Trans(p.Title, lang),
		"description":          producthelpers.Trans(p.Description, lang),
		"sku":                  p.Sku,
		"price":                pricing.Original,
		"discount":             pricing.Discounted,
		"final_price":          pricing.Final,
		"discount_expire_date": p.DiscountExpireDate,
		"stock_count":          p.StockCount,
		"views":                p.Views,
		"sales_count":          p.SalesCount,
		"weight":               p.Weight,
		"purchase_limit":       p.PurchaseLimit,
		"gender":               p.Gender,
		"is_active":            p.IsActive,
		"is_suggest":           p.IsSuggest,
		"is_pinned":            p.IsPinned,
		"category_id":          p.CategoryID,
		"brand_id":             p.BrandID,
	}

	if p.Category != nil {
		out["category"] = gin.H{"id": p.Category.ID, "name": p.Category.Name}
	}
	if p.Brand != nil {
		out["brand"] = gin.H{"id": p.Brand.ID, "name": p.Brand.Name, "image": p.Brand.Image}
	}

	colors := make([]gin.H, 0, len(p.Colors))
	for _, c := range p.Colors {
		colors = append(colors, gin.H{"id": c.ID, "name": c.Name, "hex": c.Hex})
	}
	out["colors"] = colors

	sizes := make([]gin.H, 0, len(p.Sizes))
	for _, s := range p.Sizes {
		sizeID := s.ID
		sizePricing := producthelpers.Retail(p, pivots, &sizeID)
		sizes = append(sizes, gin.H{
			"id":          s.ID,
			"name":        s.Name,
			"icon":        s.Icon,
			"price":       sizePricing.Original,
			"discount":    sizePricing.Discounted,
			"final_price": sizePricing.Final,
		})
	}
	out["sizes"] = sizes

	images := make([]gin.H, 0, len(p.Images))
	for _, img := range p.Images {
		images = append(images, gin.H{"id": img.ID, "image": helpers.StorageURL(img.ImagePath), "color_id": img.ColorID})
	}
	out["images"] = images

	videos := make([]gin.H, 0, len(p.Videos))
	for _, v := range p.Videos {
		videos = append(videos, gin.H{"id": v.ID, "video_path": helpers.StorageURL(v.VideoPath)})
	}
	out["videos"] = videos

	return out
}

// Collection maps products to their API shape.
func Collection(items []models.Product, lang string, pivotsByProduct map[int64][]models.ProductSize) []gin.H {
	out := make([]gin.H, 0, len(items))
	for _, p := range items {
		out = append(out, JSON(p, lang, pivotsByProduct[p.ID]))
	}
	return out
}
