// Package handlers holds Address module HTTP handlers.
package handlers

import (
	"net/http"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	addressrequests "shopera/internal/modules/address/requests"
	addressresponses "shopera/internal/modules/address/responses"
	addressservices "shopera/internal/modules/address/services"
)

// AddressHandler serves the address endpoints.
type AddressHandler struct {
	service *addressservices.AddressService
}

func NewAddressHandler(service *addressservices.AddressService) *AddressHandler {
	return &AddressHandler{service: service}
}

// List handles GET /api/user/address.
func (h *AddressHandler) List(c *gin.Context) {
	items, err := h.service.List(c.GetInt64("userID"))
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Addresses retrieved successfully.", items)
}

// Details handles GET /api/user/address/:id.
func (h *AddressHandler) Details(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusForbidden, "Address not found.", nil)
		return
	}
	item, err := h.service.Details(c.GetInt64("userID"), id)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	if item == nil {
		helpers.Respond(c, http.StatusForbidden, "Address not found.", nil)
		return
	}
	helpers.Respond(c, http.StatusOK, "Address retrieved successfully.", item)
}

// Add handles POST /api/user/address.
func (h *AddressHandler) Add(c *gin.Context) {
	var req addressrequests.SaveRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	address, err := h.service.Add(c.GetInt64("userID"), req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Address added successfully.", addressresponses.JSON(*address, ""))
}

// Update handles PUT /api/user/address/:id.
func (h *AddressHandler) Update(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusForbidden, "Address not found.", nil)
		return
	}
	var req addressrequests.SaveRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	address, err := h.service.Update(c.GetInt64("userID"), id, req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Address updated successfully.", addressresponses.JSON(*address, ""))
}

// Delete handles DELETE /api/user/address/:id.
func (h *AddressHandler) Delete(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusForbidden, "Address not found.", nil)
		return
	}
	if err := h.service.Delete(c.GetInt64("userID"), id); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Address deleted successfully.", nil)
}
