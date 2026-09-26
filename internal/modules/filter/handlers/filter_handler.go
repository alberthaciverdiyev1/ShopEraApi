// Package handlers holds Filter module HTTP handlers.
package handlers

import (
	"net/http"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	filterrequests "shopera/internal/modules/filter/requests"
	filterresponses "shopera/internal/modules/filter/responses"
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

// Details handles GET /api/filter/:id.
func (h *FilterHandler) Details(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Filter not found.", nil)
		return
	}
	item, err := h.service.Details(id)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Filter retrieved successfully.", item)
}

// Add handles POST /api/filter.
func (h *FilterHandler) Add(c *gin.Context) {
	var req filterrequests.SaveRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	filter, err := h.service.Create(req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Filter added successfully.", filterresponses.JSON(*filter, nil))
}

// Update handles PUT /api/filter/:id.
func (h *FilterHandler) Update(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Filter not found.", nil)
		return
	}
	var req filterrequests.SaveRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	filter, err := h.service.Update(id, req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Filter updated successfully.", filterresponses.JSON(*filter, nil))
}

// Delete handles DELETE /api/filter/:id.
func (h *FilterHandler) Delete(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Filter not found.", nil)
		return
	}
	if err := h.service.Delete(id); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Filter deleted successfully.", nil)
}

// SetCategories handles PUT /api/filter/:id/categories.
func (h *FilterHandler) SetCategories(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Filter not found.", nil)
		return
	}
	var req filterrequests.CategoryAssignRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	req.FilterID = id
	if err := h.service.SetCategories(req); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Filter categories updated successfully.", nil)
}

// ProductValues handles GET /api/product-filters?product_id=.
func (h *FilterHandler) ProductValues(c *gin.Context) {
	productID, err := helpers.QueryInt64(c, "product_id")
	if err != nil {
		helpers.Respond(c, http.StatusUnprocessableEntity, "The product_id field is required.", nil)
		return
	}
	items, err := h.service.ProductValues(productID)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Product filters retrieved successfully.", items)
}

// SetProductValues handles PUT /api/product-filters.
func (h *FilterHandler) SetProductValues(c *gin.Context) {
	var req filterrequests.ProductValuesRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	if err := h.service.SetProductValues(req); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Product filters updated successfully.", nil)
}
