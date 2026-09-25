// Package handlers holds Auth module HTTP handlers.
package handlers

import (
	"net/http"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	authrequests "shopera/internal/modules/auth/requests"
	authservices "shopera/internal/modules/auth/services"
)

// AuthHandler serves the auth endpoints.
type AuthHandler struct {
	service *authservices.AuthService
}

func NewAuthHandler(service *authservices.AuthService) *AuthHandler {
	return &AuthHandler{service: service}
}

// Register handles POST /api/auth/register.
func (h *AuthHandler) Register(c *gin.Context) {
	var req authrequests.RegisterRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}

	result, err := h.service.Register(req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "User registered successfully.", result)
}

// Login handles POST /api/auth/login.
func (h *AuthHandler) Login(c *gin.Context) {
	var req authrequests.LoginRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}

	result, err := h.service.Login(req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Login successful.", result)
}

// Logout handles POST /api/auth/logout (stateless JWT: client discards token).
func (h *AuthHandler) Logout(c *gin.Context) {
	helpers.Respond(c, http.StatusOK, "Logout successful.", nil)
}
