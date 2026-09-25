// Package requests holds Basket module request payloads.
package requests

// AddRequest is the payload for adding a basket item.
type AddRequest struct {
	ProductID int64   `json:"product_id" binding:"required"`
	Quantity  int     `json:"quantity" binding:"required,gte=1"`
	ColorID   *int64  `json:"color_id"`
	SizeID    *int64  `json:"size_id"`
	Gender    *string `json:"gender"`
}

// UpdateRequest is the payload for updating a basket item.
type UpdateRequest struct {
	Quantity *int    `json:"quantity" binding:"omitempty,gte=1"`
	ColorID  *int64  `json:"color_id"`
	SizeID   *int64  `json:"size_id"`
	Gender   *string `json:"gender"`
	Selected *bool   `json:"selected"`
}
