package handlers

import (
	"net/http"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	notificationrequests "shopera/internal/modules/notification/requests"
	notificationservices "shopera/internal/modules/notification/services"
)

// NotificationTokenHandler serves the device-token endpoint.
type NotificationTokenHandler struct {
	service *notificationservices.NotificationTokenService
}

func NewNotificationTokenHandler(service *notificationservices.NotificationTokenService) *NotificationTokenHandler {
	return &NotificationTokenHandler{service: service}
}

// SaveToken handles POST /api/notification/save-token (public, guests allowed).
func (h *NotificationTokenHandler) SaveToken(c *gin.Context) {
	var req notificationrequests.TokenRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}

	// Bind to the user when an optional bearer token is present.
	var userID *int64
	if id := c.GetInt64("userID"); id > 0 {
		userID = &id
	}

	if _, err := h.service.SaveToken(userID, req); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Notification token successfully added.", nil)
}
