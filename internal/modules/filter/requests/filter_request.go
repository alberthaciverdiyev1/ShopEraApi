// Package requests holds Filter module request payloads.
package requests

// SaveRequest creates or updates a filter.
type SaveRequest struct {
	Title   map[string]string `json:"title" binding:"required"`
	Type    string            `json:"type" binding:"required,max=16"`
	Options []string          `json:"options"`
}

// CategoryAssignRequest replaces the categories a filter is attached to.
type CategoryAssignRequest struct {
	// FilterID comes from the path param, not the body.
	FilterID    int64   `json:"filter_id"`
	CategoryIDs []int64 `json:"category_ids"`
}

// ProductValueItem is a product's value for one filter.
type ProductValueItem struct {
	FilterID int64  `json:"filter_id" binding:"required"`
	Value    string `json:"value"`
}

// ProductValuesRequest replaces a product's filter values.
type ProductValuesRequest struct {
	ProductID int64              `json:"product_id" binding:"required"`
	Values    []ProductValueItem `json:"values"`
}
