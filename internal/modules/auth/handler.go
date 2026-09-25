package auth

import (
	"net/http"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
)

// Handler serves the auth endpoints.
type Handler struct {
	service *Service
}

func NewHandler(service *Service) *Handler { return &Handler{service: service} }

// Register handles POST /api/auth/register.
func (h *Handler) Register(c *gin.Context) {
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
func (h *Handler) Login(c *gin.Context) {
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
func (h *Handler) Logout(c *gin.Context) {
	helpers.Respond(c, http.StatusOK, "Logout successful.", nil)
}
