// Package requests holds Filter module request payloads.
package requests

// CategoryFilterRequest asks for a category's filters.
type CategoryFilterRequest struct {
	CategoryID int64 `form:"category_id" json:"category_id" binding:"required"`
}
