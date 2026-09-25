package requests

// UpdatePricesRequest is the bulk price update payload.
type UpdatePricesRequest struct {
	ProductIDs         []int64  `json:"product_ids"`
	ConfirmAllProducts bool     `json:"confirm_all_products"`
	Type               string   `json:"type" binding:"required,oneof=increment decrement"`
	IsPercentage       bool     `json:"is_percentage"`
	Price              *float64 `json:"price"`
	Percentage         *float64 `json:"percentage"`
	DiscountPrice      *float64 `json:"discount_price"`
	DiscountPercentage *float64 `json:"discount_percentage"`
}
