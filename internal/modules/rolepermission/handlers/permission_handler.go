package handlers

import (
	"net/http"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	rolerequests "shopera/internal/modules/rolepermission/requests"
	roleservices "shopera/internal/modules/rolepermission/services"
)

// PermissionHandler serves the permission endpoints.
type PermissionHandler struct {
	service *roleservices.PermissionService
}

func NewPermissionHandler(service *roleservices.PermissionService) *PermissionHandler {
	return &PermissionHandler{service: service}
}

// GetAll handles GET /api/permission.
func (h *PermissionHandler) GetAll(c *gin.Context) {
	items, err := h.service.List()
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Permissions retrieved successfully.", items)
}

// Store handles POST /api/permission.
func (h *PermissionHandler) Store(c *gin.Context) {
	var req rolerequests.NameRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	p, err := h.service.Create(req.Name)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusCreated, "Permission created successfully.", p)
}

// Show handles GET /api/permission/:id.
func (h *PermissionHandler) Show(c *gin.Context) {
	id, ok := parseInt(c.Param("id"))
	if !ok {
		helpers.Respond(c, http.StatusNotFound, "Permission not found.", nil)
		return
	}
	p, err := h.service.Get(id)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	if p == nil {
		helpers.Respond(c, http.StatusNotFound, "Permission not found.", nil)
		return
	}
	helpers.Respond(c, http.StatusOK, "Permission details retrieved successfully.", p)
}

// Update handles PUT /api/permission/:permission.
func (h *PermissionHandler) Update(c *gin.Context) {
	id, ok := parseInt(c.Param("permission"))
	if !ok {
		helpers.Respond(c, http.StatusNotFound, "Permission not found.", nil)
		return
	}
	var req rolerequests.NameRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	p, err := h.service.Update(id, req.Name)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Permission updated successfully.", p)
}

// Delete handles DELETE /api/permission/:permission.
func (h *PermissionHandler) Delete(c *gin.Context) {
	id, ok := parseInt(c.Param("permission"))
	if !ok {
		helpers.Respond(c, http.StatusNotFound, "Permission not found.", nil)
		return
	}
	if err := h.service.Delete(id); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Permission deleted successfully.", nil)
}
