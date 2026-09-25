package requests

// SubscribeRequest is the stock-subscription payload.
type SubscribeRequest struct {
	ProductID int64 `json:"product_id" form:"product_id" binding:"required"`
}
