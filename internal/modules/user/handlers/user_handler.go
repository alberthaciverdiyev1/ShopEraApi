// Package handlers holds User module HTTP handlers.
package handlers

import (
	"net/http"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	userrequests "shopera/internal/modules/user/requests"
	userservices "shopera/internal/modules/user/services"
)

// UserHandler serves profile-change endpoints.
type UserHandler struct {
	service *userservices.UserService
}

func NewUserHandler(service *userservices.UserService) *UserHandler {
	return &UserHandler{service: service}
}

// ChangeEmail handles PUT /user/change-email.
func (h *UserHandler) ChangeEmail(c *gin.Context) {
	var req userrequests.ChangeEmailRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}

	user, err := h.service.ChangeEmail(c.GetInt64("userID"), req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Email changed successfully", user)
}

// ChangeName handles PUT /user/change-name.
func (h *UserHandler) ChangeName(c *gin.Context) {
	var req userrequests.ChangeNameRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}

	user, err := h.service.ChangeName(c.GetInt64("userID"), req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Name changed successfully", user)
}

// ChangeSurname handles PUT /user/change-surname.
func (h *UserHandler) ChangeSurname(c *gin.Context) {
	var req userrequests.ChangeSurnameRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}

	user, err := h.service.ChangeSurname(c.GetInt64("userID"), req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Surname changed successfully", user)
}

// ChangePhone handles PUT /user/change-phone.
func (h *UserHandler) ChangePhone(c *gin.Context) {
	var req userrequests.ChangePhoneRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}

	user, err := h.service.ChangePhone(c.GetInt64("userID"), req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Phone changed successfully", user)
}
