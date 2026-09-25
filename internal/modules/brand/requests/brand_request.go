// Package requests holds Brand module request payloads.
package requests

// SaveRequest is used for both create and update (JSON or multipart).
type SaveRequest struct {
	Name      string  `json:"name" form:"name" binding:"required,max=255"`
	Image     *string `json:"image" form:"image"`
	IsActive  *bool   `json:"is_active" form:"is_active"`
	SortOrder *int    `json:"sort_order" form:"sort_order" binding:"omitempty,gte=1"`
}
