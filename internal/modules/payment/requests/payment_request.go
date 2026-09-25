// Package requests holds Payment module request payloads.
package requests

// SaveProviderRequest stores a provider's name/state/credentials.
type SaveProviderRequest struct {
	Key       string            `json:"key" binding:"required"`
	Name      string            `json:"name" binding:"required"`
	IsActive  *bool             `json:"is_active"`
	SortOrder *int              `json:"sort_order"`
	Config    map[string]string `json:"config"`
}

// CreatePaymentRequest starts a payment for an order.
type CreatePaymentRequest struct {
	OrderID  string  `json:"order_id" binding:"required"`
	Amount   float64 `json:"amount" binding:"required,gt=0"`
	Method   string  `json:"method"`
	Language string  `json:"language"`
}
