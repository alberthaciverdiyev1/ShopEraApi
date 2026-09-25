// Package requests holds Category module request payloads.
package requests

// SaveRequest is used for both create and update (JSON or multipart).
type SaveRequest struct {
	Name        map[string]string `json:"name" form:"name"`
	Image       *string           `json:"image" form:"image"`
	Description *string           `json:"description" form:"description"`
	ParentID    *int64            `json:"parent_id" form:"parent_id"`
	IsActive    *bool             `json:"is_active" form:"is_active"`
	SortOrder   *int              `json:"sort_order" form:"sort_order"`
}
