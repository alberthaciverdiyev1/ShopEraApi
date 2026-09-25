// Package handlers holds RoleAndPermissions HTTP handlers.
package handlers

import (
	"net/http"
	"strconv"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	rolerequests "shopera/internal/modules/rolepermission/requests"
	roleresponses "shopera/internal/modules/rolepermission/responses"
	roleservices "shopera/internal/modules/rolepermission/services"
)

// RoleHandler serves the role endpoints.
type RoleHandler struct {
	service *roleservices.RoleService
}

func NewRoleHandler(service *roleservices.RoleService) *RoleHandler {
	return &RoleHandler{service: service}
}

// GetAll handles GET /api/role.
func (h *RoleHandler) GetAll(c *gin.Context) {
	withPerms := c.Query("permission") == "1" || c.Query("permission") == "true"
	items, err := h.service.List(withPerms)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Roles retrieved successfully.", items)
}

// Add handles POST /api/role.
func (h *RoleHandler) Add(c *gin.Context) {
	var req rolerequests.NameRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	role, err := h.service.Add(req.Name)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusCreated, "Role created successfully.", roleresponses.RoleJSON(*role, nil))
}

// Details handles GET /api/role/:id.
func (h *RoleHandler) Details(c *gin.Context) {
	id, ok := parseInt(c.Param("id"))
	if !ok {
		helpers.Respond(c, http.StatusNotFound, "Role not found.", nil)
		return
	}
	item, err := h.service.Details(id)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	if item == nil {
		helpers.Respond(c, http.StatusNotFound, "Role not found.", nil)
		return
	}
	helpers.Respond(c, http.StatusOK, "Role details retrieved successfully.", item)
}

// Update handles PUT /api/role/:role.
func (h *RoleHandler) Update(c *gin.Context) {
	id, ok := parseInt(c.Param("role"))
	if !ok {
		helpers.Respond(c, http.StatusNotFound, "Role not found.", nil)
		return
	}
	var req rolerequests.NameRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	role, err := h.service.Update(id, req.Name)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Role updated successfully.", roleresponses.RoleJSON(*role, nil))
}

// Delete handles DELETE /api/role/:role.
func (h *RoleHandler) Delete(c *gin.Context) {
	id, ok := parseInt(c.Param("role"))
	if !ok {
		helpers.Respond(c, http.StatusNotFound, "Role not found.", nil)
		return
	}
	if err := h.service.Delete(id); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Role deleted successfully.", nil)
}

// GivePermission handles POST /api/role/:role/give-permission.
func (h *RoleHandler) GivePermission(c *gin.Context) {
	id, ok := parseInt(c.Param("role"))
	if !ok {
		helpers.Respond(c, http.StatusNotFound, "Role not found.", nil)
		return
	}
	var req rolerequests.PermissionNameRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	perms, err := h.service.GivePermission(id, req.Permission)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Permission assigned to role successfully.", perms)
}

// RevokePermission handles POST /api/role/:role/revoke-permission.
func (h *RoleHandler) RevokePermission(c *gin.Context) {
	id, ok := parseInt(c.Param("role"))
	if !ok {
		helpers.Respond(c, http.StatusNotFound, "Role not found.", nil)
		return
	}
	var req rolerequests.PermissionNameRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	perms, err := h.service.RevokePermission(id, req.Permission)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Permission revoked from role successfully.", perms)
}

// AssignRoleToUser handles POST /api/role/assign-role/:userId.
func (h *RoleHandler) AssignRoleToUser(c *gin.Context) {
	userID, ok := parseInt(c.Param("userId"))
	if !ok {
		helpers.Respond(c, http.StatusNotFound, "User not found.", nil)
		return
	}
	var req rolerequests.RoleNameRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	roles, err := h.service.AssignToUser(userID, req.Role)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Role assigned to user successfully.", roles)
}

// RevokeRoleFromUser handles POST /api/role/revoke-role/:userId.
func (h *RoleHandler) RevokeRoleFromUser(c *gin.Context) {
	userID, ok := parseInt(c.Param("userId"))
	if !ok {
		helpers.Respond(c, http.StatusNotFound, "User not found.", nil)
		return
	}
	var req rolerequests.RoleNameRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	roles, err := h.service.RevokeFromUser(userID, req.Role)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Role removed from user successfully.", roles)
}

func parseInt(value string) (int64, bool) {
	n, err := strconv.ParseInt(value, 10, 64)
	return n, err == nil
}
