package handlers

import (
	"net/http"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	deliveryrequests "shopera/internal/modules/delivery/requests"
	deliveryresponses "shopera/internal/modules/delivery/responses"
	deliveryservices "shopera/internal/modules/delivery/services"
)

// PickupPointHandler serves the pickup-point endpoints.
type PickupPointHandler struct {
	service *deliveryservices.PickupPointService
}

func NewPickupPointHandler(service *deliveryservices.PickupPointService) *PickupPointHandler {
	return &PickupPointHandler{service: service}
}

// List handles GET /api/pickup-point.
func (h *PickupPointHandler) List(c *gin.Context) {
	isAdmin := c.Query("is_admin") == "1" || c.Query("is_admin") == "true"
	result, err := h.service.List(helpers.ParseQuery(c), helpers.QueryBoolPtr(c, "is_active"), isAdmin)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Pickup points retrieved successfully.", result)
}

// Details handles GET /api/pickup-point/details?id=.
func (h *PickupPointHandler) Details(c *gin.Context) {
	id, err := helpers.QueryInt64(c, "id")
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Pickup point not found.", nil)
		return
	}
	item, err := h.service.Details(id)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Pickup point details retrieved successfully.", deliveryresponses.PickupPointJSON(*item, false))
}

// DetailsAdmin handles GET /api/pickup-point/:id.
func (h *PickupPointHandler) DetailsAdmin(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Pickup point not found.", nil)
		return
	}
	item, err := h.service.DetailsAdmin(id)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	admin := c.Query("is_admin") == "1" || c.Query("is_admin") == "true"
	helpers.Respond(c, http.StatusOK, "Pickup point details retrieved successfully.", deliveryresponses.PickupPointJSON(*item, admin))
}

// Add handles POST /api/pickup-point.
func (h *PickupPointHandler) Add(c *gin.Context) {
	var req deliveryrequests.PickupPointSaveRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	item, err := h.service.Add(req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusCreated, "Pickup point added successfully.", deliveryresponses.PickupPointJSON(*item, false))
}

// Update handles PUT /api/pickup-point/:id.
func (h *PickupPointHandler) Update(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Pickup point not found.", nil)
		return
	}
	var req deliveryrequests.PickupPointSaveRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	item, err := h.service.Update(id, req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Pickup point updated successfully.", deliveryresponses.PickupPointJSON(*item, false))
}

// Delete handles DELETE /api/pickup-point/:id.
func (h *PickupPointHandler) Delete(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Pickup point not found.", nil)
		return
	}
	if err := h.service.Delete(id); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Pickup point deleted successfully.", nil)
}
