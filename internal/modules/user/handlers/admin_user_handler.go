package handlers

import (
	"net/http"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	userrequests "shopera/internal/modules/user/requests"
	userservices "shopera/internal/modules/user/services"
)

// AdminUserHandler serves user-management endpoints.
type AdminUserHandler struct {
	service *userservices.AdminUserService
}

func NewAdminUserHandler(service *userservices.AdminUserService) *AdminUserHandler {
	return &AdminUserHandler{service: service}
}

// Block handles POST /user/block.
func (h *AdminUserHandler) Block(c *gin.Context) {
	var req userrequests.BlockUserRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	if req.Block == nil {
		helpers.ValidationFailed(c, errBoolRequired{field: "block"})
		return
	}

	user, err := h.service.Block(c.GetInt64("userID"), req.UserID, *req.Block)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	message := "User blocked successfully"
	if !*req.Block {
		message = "User unblocked successfully"
	}
	helpers.Respond(c, http.StatusOK, message, user)
}

// ChangeWholesalerStatus handles PUT /user/wholesaler-status.
func (h *AdminUserHandler) ChangeWholesalerStatus(c *gin.Context) {
	var req userrequests.WholesalerStatusRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	if req.IsWholesaler == nil {
		helpers.ValidationFailed(c, errBoolRequired{field: "is_wholesaler"})
		return
	}

	user, err := h.service.ChangeWholesalerStatus(req.UserID, *req.IsWholesaler)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Wholesale status updated successfully", user)
}

// Details handles GET /user/details and GET /user/details/:id (id optional).
func (h *AdminUserHandler) Details(c *gin.Context) {
	var targetID *int64
	if raw := c.Param("id"); raw != "" {
		id, err := helpers.PathID(c)
		if err != nil {
			helpers.Respond(c, http.StatusNotFound, "User not found", nil)
			return
		}
		targetID = &id
	}

	data, err := h.service.Details(c.GetInt64("userID"), targetID)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "User retrieved successfully", data)
}

// Delete handles DELETE /user/delete-admin/:id.
func (h *AdminUserHandler) Delete(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "User not found", nil)
		return
	}

	if err := h.service.Delete(c.GetInt64("userID"), id); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "User deleted successfully", nil)
}

// DeleteMyAccount handles DELETE /user/delete.
func (h *AdminUserHandler) DeleteMyAccount(c *gin.Context) {
	if err := h.service.DeleteMyAccount(c.GetInt64("userID")); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "User deleted successfully", nil)
}

// errBoolRequired reports a missing boolean body field.
type errBoolRequired struct{ field string }

func (e errBoolRequired) Error() string { return "The " + e.field + " field is required." }
