// Package requests holds HelpAndPolicy module request payloads.
package requests

// FaqSaveRequest is used for create and update.
type FaqSaveRequest struct {
	Title       map[string]string `json:"title"`
	Description map[string]string `json:"description"`
	Type        *string           `json:"type"`
}
