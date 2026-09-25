// Package requests holds Review module request payloads.
package requests

// AddRequest is the payload for adding a review.
type AddRequest struct {
	ProductID int64   `json:"product_id" form:"product_id" binding:"required"`
	Rate      int     `json:"rate" form:"rate" binding:"required,gte=1,lte=5"`
	Comment   *string `json:"comment" form:"comment"`
}

// ChangeStatusRequest changes a review's status.
type ChangeStatusRequest struct {
	ReviewID int64  `json:"review_id" binding:"required"`
	Status   string `json:"status" binding:"required"`
}
