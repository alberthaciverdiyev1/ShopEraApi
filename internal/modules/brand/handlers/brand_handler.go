// Package handlers holds Brand module HTTP handlers.
package handlers

import (
	"net/http"
	"strconv"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	brandrequests "shopera/internal/modules/brand/requests"
	brandresponses "shopera/internal/modules/brand/responses"
	brandservices "shopera/internal/modules/brand/services"
)

// BrandHandler serves the brand endpoints.
type BrandHandler struct {
	service *brandservices.BrandService
}

func NewBrandHandler(service *brandservices.BrandService) *BrandHandler {
	return &BrandHandler{service: service}
}

// List handles GET /api/brand.
func (h *BrandHandler) List(c *gin.Context) {
	var isActive *bool
	if v := c.Query("is_active"); v != "" {
		b := v == "1" || v == "true"
		isActive = &b
	}

	result, err := h.service.List(c.Query("search"), isActive, helpers.QueryInt(c, "page", 1))
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Brands retrieved successfully.", result)
}

// Details handles GET /api/brand/:id.
func (h *BrandHandler) Details(c *gin.Context) {
	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		helpers.Respond(c, http.StatusForbidden, "Brand not found.", nil)
		return
	}

	item, err := h.service.Details(id)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	if item == nil {
		helpers.Respond(c, http.StatusForbidden, "Brand not found.", nil)
		return
	}
	helpers.Respond(c, http.StatusOK, "Brand details retrieved successfully.", brandresponses.JSON(*item))
}

// Add handles POST /api/brand.
func (h *BrandHandler) Add(c *gin.Context) {
	var req brandrequests.SaveRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}

	brand, err := h.service.Add(req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Brand added successfully.", brandresponses.JSON(*brand))
}

// Update handles PUT /api/brand/:id.
func (h *BrandHandler) Update(c *gin.Context) {
	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		helpers.Respond(c, http.StatusForbidden, "Brand not found.", nil)
		return
	}

	var req brandrequests.SaveRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}

	brand, err := h.service.Update(id, req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Brand updated successfully.", brandresponses.JSON(*brand))
}

// Delete handles DELETE /api/brand/:id.
func (h *BrandHandler) Delete(c *gin.Context) {
	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		helpers.Respond(c, http.StatusForbidden, "Brand not found.", nil)
		return
	}

	if err := h.service.Delete(id); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Brand deleted successfully.", nil)
}
