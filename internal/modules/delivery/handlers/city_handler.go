// Package handlers holds Delivery module HTTP handlers.
package handlers

import (
	"net/http"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	cityservices "shopera/internal/modules/delivery/services"
)

// CityHandler serves the location endpoints.
type CityHandler struct {
	service *cityservices.CityService
}

func NewCityHandler(service *cityservices.CityService) *CityHandler {
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
