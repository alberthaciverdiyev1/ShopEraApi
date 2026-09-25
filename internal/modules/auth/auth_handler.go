package auth

import (
	"net/http"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
)

// AuthHandler serves the auth endpoints.
type AuthHandler struct {
	service *AuthService
}

func NewAuthHandler(service *AuthService) *AuthHandler { return &AuthHandler{service: service} }

// Register handles POST /api/auth/register.
func (h *AuthHandler) Register(c *gin.Context) {
	var req RegisterRequest
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
	var req LoginRequest
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
