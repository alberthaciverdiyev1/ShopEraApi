// Package requests holds Size module request payloads.
package requests

// SaveRequest is used for both create and update.
type SaveRequest struct {
	Name      string  `json:"name" binding:"required,max=255"`
	Icon      *string `json:"icon"`
	IsActive  *bool   `json:"is_active"`
	SortOrder *int    `json:"sort_order" binding:"omitempty,gte=1"`
}
