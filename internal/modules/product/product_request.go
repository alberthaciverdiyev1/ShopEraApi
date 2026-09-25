package product

// CreateRequest is the payload for adding a product.
type CreateRequest struct {
	Title      map[string]string `json:"title" binding:"required"`
	Price      *float64          `json:"price"`
	StockCount int               `json:"stock_count" binding:"gte=0"`
	IsActive   bool              `json:"is_active"`
}
