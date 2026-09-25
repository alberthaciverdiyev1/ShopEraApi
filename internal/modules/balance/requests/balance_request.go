// Package requests holds Balance module request payloads.
package requests

// DepositRequest creates a balance entry.
type DepositRequest struct {
	UserID *int64  `json:"user_id"`
	Amount float64 `json:"amount" binding:"required,gt=0"`
	Note   *string `json:"note"`
	Type   *string `json:"type"`
}

// IncreaseRequest starts a card top-up.
type IncreaseRequest struct {
	Amount float64 `json:"amount" binding:"required,gte=0.1"`
}
