// Package handlers holds Filter module HTTP handlers.
package handlers

import (
	"net/http"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	filterservices "shopera/internal/modules/filter/services"
)

// FilterHandler serves the filter endpoints.
type FilterHandler struct {
	service *filterservices.FilterService
}

func NewFilterHandler(service *filterservices.FilterService) *FilterHandler {
	return &FilterHandler{service: service}
}

// List handles GET /api/filters.
func (h *FilterHandler) List(c *gin.Context) {
	items, err := h.service.List()
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Filters retrieved successfully.", items)
}

// CategoryFilters handles GET /api/category-filters?category_id=.
func (h *FilterHandler) CategoryFilters(c *gin.Context) {
	categoryID, err := helpers.QueryInt64(c, "category_id")
	if err != nil {
		helpers.Respond(c, http.StatusUnprocessableEntity, "The category_id field is required.", nil)
		return
	}
	items, err := h.service.CategoryFilters(categoryID)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Filters retrieved successfully.", items)
}
