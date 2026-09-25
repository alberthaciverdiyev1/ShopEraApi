// Package requests holds Category module request payloads.
package requests

// SaveRequest is used for both create and update.
type SaveRequest struct {
	Name        map[string]string `json:"name"`
	Image       *string           `json:"image"`
	Description *string           `json:"description"`
	ParentID    *int64            `json:"parent_id"`
	IsActive    *bool             `json:"is_active"`
	SortOrder   *int              `json:"sort_order"`
}
