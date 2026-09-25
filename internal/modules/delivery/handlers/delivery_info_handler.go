package handlers

import (
	"net/http"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	deliveryrequests "shopera/internal/modules/delivery/requests"
	deliveryresponses "shopera/internal/modules/delivery/responses"
	deliveryservices "shopera/internal/modules/delivery/services"
)

// DeliveryInfoHandler serves the delivery-info endpoints.
type DeliveryInfoHandler struct {
	service *deliveryservices.DeliveryInfoService
}

func NewDeliveryInfoHandler(service *deliveryservices.DeliveryInfoService) *DeliveryInfoHandler {
	return &DeliveryInfoHandler{service: service}
}

// List handles GET /api/delivery-info?type=.
func (h *DeliveryInfoHandler) List(c *gin.Context) {
	items, err := h.service.List(c.Query("type"))
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "", deliveryresponses.DeliveryInfoCollection(items))
}

// ByType handles GET /api/delivery-info/:id (id = type).
func (h *DeliveryInfoHandler) ByType(c *gin.Context) {
	item, err := h.service.ByType(c.Param("id"))
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	if item == nil {
		helpers.Respond(c, http.StatusOK, "", nil)
		return
	}
	helpers.Respond(c, http.StatusOK, "", deliveryresponses.DeliveryInfoJSON(*item))
}

// Update handles PUT /api/delivery-info/:id.
func (h *DeliveryInfoHandler) Update(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Delivery info not found.", nil)
		return
	}
	var req deliveryrequests.DeliveryInfoSaveRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	item, err := h.service.Update(id, req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Delivery info updated successfully", deliveryresponses.DeliveryInfoJSON(*item))
}
