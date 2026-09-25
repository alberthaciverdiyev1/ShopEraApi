// Package requests holds PromoCode module request payloads.
package requests

// SaveRequest is used for create and update.
type SaveRequest struct {
	Code            string   `json:"code" binding:"required,max=255"`
	DiscountPercent *float64 `json:"discount_percent" binding:"required,gt=0,lte=100"`
	UserCount       *int     `json:"user_count" binding:"required,gte=1"`
	IsActive        *bool    `json:"is_active"`
}
