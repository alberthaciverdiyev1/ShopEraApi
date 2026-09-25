// Package providers defines the pluggable payment-provider contract and registry.
package providers

import "context"

// InitiateRequest is the data a provider needs to start a payment.
type InitiateRequest struct {
	OrderID     string
	Amount      float64
	Currency    string
	Description string
	SuccessURL  string
	ErrorURL    string
	Language    string
}

// CallbackResult is the parsed outcome of a provider callback.
type CallbackResult struct {
	Provider      string
	OrderID       string
	TransactionID string
	Paid          bool
	Raw           map[string]string
}

// Provider is a payment gateway (Epoint, ...).
type Provider interface {
	// Key is the stable provider identifier stored in the DB.
	Key() string
	// Initiate starts a payment and returns where to redirect the user.
	Initiate(ctx context.Context, req InitiateRequest, config map[string]string) (redirectURL string, err error)
	// VerifyCallback validates a callback signature and parses its result.
	VerifyCallback(params map[string]string, config map[string]string) (*CallbackResult, error)
}
