// Package contract defines the shared payment-provider contract.
// Each provider implements its own specifics in its own package.
package contract

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

// Provider is implemented by every payment gateway.
//
// NOTE: providers do NOT all work the same way. The contract only fixes what the
// rest of the app needs; each provider implements its own signature, endpoints
// and callback parsing inside its own package.
type Provider interface {
	Key() string
	Name() string
	Initiate(ctx context.Context, req InitiateRequest, config map[string]string) (redirectURL string, err error)
	VerifyCallback(params map[string]string, config map[string]string) (*CallbackResult, error)
}
