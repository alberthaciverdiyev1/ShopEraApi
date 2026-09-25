// Package handlers holds Notification module HTTP handlers.
package handlers

import (
	"net/http"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	notificationrequests "shopera/internal/modules/notification/requests"
	notificationresponses "shopera/internal/modules/notification/responses"
	notificationservices "shopera/internal/modules/notification/services"
)

// NotificationHandler serves the notification endpoints.
type NotificationHandler struct {
	service *notificationservices.NotificationService
}

func NewNotificationHandler(service *notificationservices.NotificationService) *NotificationHandler {
	return &NotificationHandler{service: service}
}

// List handles GET /api/notification.
func (h *NotificationHandler) List(c *gin.Context) {
	result, err := h.service.List(c.GetInt64("userID"), helpers.ParseQuery(c))
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Notifications retrieved successfully.", result)
}

// ListAdmin handles GET /api/notification/admin.
func (h *NotificationHandler) ListAdmin(c *gin.Context) {
	source := c.Query("source")
	if source == "" {
		source = "admin"
	}
	result, err := h.service.ListAdmin(helpers.ParseQuery(c), source)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Notifications retrieved successfully.", result)
}

// Send handles POST /api/notification.
func (h *NotificationHandler) Send(c *gin.Context) {
	var req notificationrequests.SendRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	notification, err := h.service.Send(c.Request.Context(), req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Notification sent successfully.", notificationresponses.JSON(*notification))
}

// Delete handles DELETE /api/notification/:id.
func (h *NotificationHandler) Delete(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Notification not found.", nil)
		return
	}
	if err := h.service.Delete(id); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Notifications deleted successfully.", nil)
}
