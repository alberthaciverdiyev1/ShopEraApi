// Package handlers holds Color module HTTP handlers.
package handlers

import (
	"net/http"
	"strconv"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	colorrequests "shopera/internal/modules/color/requests"
	colorresponses "shopera/internal/modules/color/responses"
	colorservices "shopera/internal/modules/color/services"
)

// ColorHandler serves the color endpoints.
type ColorHandler struct {
	service *colorservices.ColorService
}

func NewColorHandler(service *colorservices.ColorService) *ColorHandler {
	return &ColorHandler{service: service}
}

// List handles GET /api/color.
func (h *ColorHandler) List(c *gin.Context) {
	result, err := h.service.List(helpers.ParseQuery(c))
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Colors retrieved successfully.", result)
}

// Details handles GET /api/color/:id.
func (h *ColorHandler) Details(c *gin.Context) {
	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		helpers.Respond(c, http.StatusForbidden, "Colors not found.", nil)
		return
	}
	item, err := h.service.Details(id)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	if item == nil {
		helpers.Respond(c, http.StatusForbidden, "Colors not found.", nil)
		return
	}
	helpers.Respond(c, http.StatusOK, "Colors retrieved successfully.", colorresponses.JSON(*item))
}

// Add handles POST /api/color.
func (h *ColorHandler) Add(c *gin.Context) {
	var req colorrequests.SaveRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	color, err := h.service.Add(req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Color added successfully.", colorresponses.JSON(*color))
}

// Update handles PUT /api/color/:id.
func (h *ColorHandler) Update(c *gin.Context) {
	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		helpers.Respond(c, http.StatusForbidden, "Colors not found.", nil)
		return
	}
	var req colorrequests.SaveRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	color, err := h.service.Update(id, req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Color updated successfully.", colorresponses.JSON(*color))
}

// Delete handles DELETE /api/color/:id.
func (h *ColorHandler) Delete(c *gin.Context) {
	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		helpers.Respond(c, http.StatusForbidden, "Colors not found.", nil)
		return
	}
	if err := h.service.Delete(id); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Color deleted successfully.", nil)
}
