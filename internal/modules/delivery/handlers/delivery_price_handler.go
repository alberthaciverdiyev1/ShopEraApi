package handlers

import (
	"net/http"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	deliveryrequests "shopera/internal/modules/delivery/requests"
	deliveryresponses "shopera/internal/modules/delivery/responses"
	deliveryservices "shopera/internal/modules/delivery/services"
)

// DeliveryPriceHandler serves the delivery-price endpoints.
type DeliveryPriceHandler struct {
	service *deliveryservices.DeliveryPriceService
}

func NewDeliveryPriceHandler(service *deliveryservices.DeliveryPriceService) *DeliveryPriceHandler {
	return &DeliveryPriceHandler{service: service}
}

// List handles GET /api/delivery.
func (h *DeliveryPriceHandler) List(c *gin.Context) {
	isAdmin := c.Query("is_admin") == "1" || c.Query("is_admin") == "true"
	result, err := h.service.List(helpers.ParseQuery(c), isAdmin)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Deliveries retrieved successfully.", result)
}

// Details handles GET /api/delivery/details?city_name=.
func (h *DeliveryPriceHandler) Details(c *gin.Context) {
	h.details(c, false)
}

// DetailsForMobile handles GET /api/delivery/details-mobile?city_name=.
func (h *DeliveryPriceHandler) DetailsForMobile(c *gin.Context) {
	h.details(c, true)
}

func (h *DeliveryPriceHandler) details(c *gin.Context, _ bool) {
	name := c.Query("city_name")
	if name == "" {
		helpers.Respond(c, http.StatusForbidden, "Delivery service is not available for your city.", nil)
		return
	}
	item, err := h.service.Details(name)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	if item == nil {
		helpers.Respond(c, http.StatusForbidden, "Delivery not found.", []any{})
		return
	}
	helpers.Respond(c, http.StatusOK, "Delivery details retrieved successfully.", deliveryresponses.DeliveryPriceJSON(*item))
}

// Cities handles GET /api/city.
func (h *DeliveryPriceHandler) Cities(c *gin.Context) {
	items, err := h.service.Cities()
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Cities retrieved successfully.", items)
}

// Add handles POST /api/delivery.
func (h *DeliveryPriceHandler) Add(c *gin.Context) {
	var req deliveryrequests.DeliverySaveRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	item, message, err := h.service.Add(req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, message, deliveryresponses.DeliveryPriceJSON(*item))
}

// Update handles PUT /api/delivery/:id.
func (h *DeliveryPriceHandler) Update(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Delivery not found.", nil)
		return
	}
	var req deliveryrequests.DeliverySaveRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	item, err := h.service.Update(id, req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Delivery updated successfully.", deliveryresponses.DeliveryPriceJSON(*item))
}

// Delete handles DELETE /api/delivery/:id.
func (h *DeliveryPriceHandler) Delete(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Delivery not found.", nil)
		return
	}
	if err := h.service.Delete(id); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Delivery deleted successfully.", nil)
}
