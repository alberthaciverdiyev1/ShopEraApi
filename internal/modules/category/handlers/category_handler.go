// Package handlers holds Category module HTTP handlers.
package handlers

import (
	"net/http"
	"strconv"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	categoryrequests "shopera/internal/modules/category/requests"
	categoryresponses "shopera/internal/modules/category/responses"
	categoryservices "shopera/internal/modules/category/services"
)

// CategoryHandler serves the category endpoints.
type CategoryHandler struct {
	service *categoryservices.CategoryService
}

func NewCategoryHandler(service *categoryservices.CategoryService) *CategoryHandler {
	return &CategoryHandler{service: service}
}

// List handles GET /api/category.
func (h *CategoryHandler) List(c *gin.Context) {
	onlyParents := c.Query("all") == ""
	items, err := h.service.List(helpers.ParseQuery(c), onlyParents)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Categories retrieved successfully.", categoryresponses.Collection(items))
}

// ListAdmin handles GET /api/category/admin.
func (h *CategoryHandler) ListAdmin(c *gin.Context) {
	onlyParents := c.Query("all") == ""
	items, err := h.service.List(helpers.ParseQuery(c), onlyParents)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Categories retrieved successfully.", categoryresponses.Collection(items))
}

// Details handles GET /api/category/:id.
func (h *CategoryHandler) Details(c *gin.Context) {
	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		helpers.Respond(c, http.StatusForbidden, "Category not found.", nil)
		return
	}

	item, err := h.service.Details(id)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	if item == nil {
		helpers.Respond(c, http.StatusForbidden, "Category not found.", nil)
		return
	}
	helpers.Respond(c, http.StatusOK, "Category details retrieved successfully.", categoryresponses.JSON(*item))
}

// Add handles POST /api/category.
func (h *CategoryHandler) Add(c *gin.Context) {
	var req categoryrequests.SaveRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}

	category, err := h.service.Add(req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Category added successfully.", categoryresponses.JSON(*category))
}

// Update handles PUT /api/category/:id.
func (h *CategoryHandler) Update(c *gin.Context) {
	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		helpers.Respond(c, http.StatusForbidden, "Category not found.", nil)
		return
	}

	var req categoryrequests.SaveRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}

	category, err := h.service.Update(id, req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Category updated successfully", categoryresponses.JSON(*category))
}

// Delete handles DELETE /api/category/:id.
func (h *CategoryHandler) Delete(c *gin.Context) {
	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		helpers.Respond(c, http.StatusForbidden, "Category not found.", nil)
		return
	}

	if err := h.service.Delete(id); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Category deleted successfully.", nil)
}
