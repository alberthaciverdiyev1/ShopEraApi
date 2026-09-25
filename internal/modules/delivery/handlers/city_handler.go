// Package handlers holds Delivery module HTTP handlers.
package handlers

import (
	"net/http"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	deliveryrequests "shopera/internal/modules/delivery/requests"
	deliveryservices "shopera/internal/modules/delivery/services"
)

// CityHandler serves the location endpoints.
type CityHandler struct {
	service *deliveryservices.CityService
}

func NewCityHandler(service *deliveryservices.CityService) *CityHandler {
	return &CityHandler{service: service}
}

// List handles GET /api/cities.
func (h *CityHandler) List(c *gin.Context) {
	items, err := h.service.List()
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Cities retrieved successfully.", items)
}

// Options handles GET /api/delivery-city.
func (h *CityHandler) Options(c *gin.Context) {
	items, err := h.service.Options()
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Cities retrieved successfully.", items)
}

// Add handles POST /api/delivery-city.
func (h *CityHandler) Add(c *gin.Context) {
	var req deliveryrequests.CitySaveRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	city, err := h.service.Add(req.Name)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusCreated, "City added successfully.", gin.H{"id": city.Key, "key": city.Key, "name": city.Name})
}

// Update handles PUT /api/delivery-city/:id (id = key).
func (h *CityHandler) Update(c *gin.Context) {
	var req deliveryrequests.CitySaveRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	city, err := h.service.Update(c.Param("id"), req.Name)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "City updated successfully.", gin.H{"id": city.Key, "key": city.Key, "name": city.Name})
}

// Delete handles DELETE /api/delivery-city/:id (id = key).
func (h *CityHandler) Delete(c *gin.Context) {
	if err := h.service.Delete(c.Param("id")); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "City deleted successfully.", nil)
}

// Details handles GET /api/delivery-city/details/:id (id = key).
func (h *CityHandler) Details(c *gin.Context) {
	city, err := h.service.Details(c.Param("id"))
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "City details retrieved.", gin.H{"id": city.Key, "key": city.Key, "name": city.Name})
}
