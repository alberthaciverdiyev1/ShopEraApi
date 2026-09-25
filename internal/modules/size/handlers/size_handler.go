// Package handlers holds Size module HTTP handlers.
package handlers

import (
	"net/http"
	"strconv"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	sizerequests "shopera/internal/modules/size/requests"
	sizeresponses "shopera/internal/modules/size/responses"
	sizeservices "shopera/internal/modules/size/services"
)

// SizeHandler serves the size endpoints.
type SizeHandler struct {
	service *sizeservices.SizeService
}

func NewSizeHandler(service *sizeservices.SizeService) *SizeHandler {
	return &SizeHandler{service: service}
}

// List handles GET /api/size.
func (h *SizeHandler) List(c *gin.Context) {
	result, err := h.service.List(helpers.ParseQuery(c))
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Sizes retrieved successfully.", result)
}

// Details handles GET /api/size/:id.
func (h *SizeHandler) Details(c *gin.Context) {
	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		helpers.Respond(c, http.StatusOK, "Size not found.", nil)
		return
	}
	item, err := h.service.Details(id)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	if item == nil {
		helpers.Respond(c, http.StatusOK, "Size not found.", nil)
		return
	}
	helpers.Respond(c, http.StatusOK, "Size details retrieved successfully.", sizeresponses.JSON(*item))
}

// Add handles POST /api/size.
func (h *SizeHandler) Add(c *gin.Context) {
	var req sizerequests.SaveRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	size, err := h.service.Add(req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Size added successfully.", sizeresponses.JSON(*size))
}

// Update handles PUT /api/size/:id.
func (h *SizeHandler) Update(c *gin.Context) {
	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		helpers.Respond(c, http.StatusOK, "Size not found.", nil)
		return
	}
	var req sizerequests.SaveRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	size, err := h.service.Update(id, req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Size updated successfully.", sizeresponses.JSON(*size))
}

// Delete handles DELETE /api/size/:id.
func (h *SizeHandler) Delete(c *gin.Context) {
	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		helpers.Respond(c, http.StatusOK, "Size not found.", nil)
		return
	}
	if err := h.service.Delete(id); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Size deleted successfully.", nil)
}
