package handlers

import (
	"net/http"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	chatrequests "shopera/internal/modules/chat/requests"
	chatresponses "shopera/internal/modules/chat/responses"
	chatservices "shopera/internal/modules/chat/services"
)

// AutoReplyHandler serves the auto-reply endpoints.
type AutoReplyHandler struct {
	service *chatservices.AutoReplyService
}

func NewAutoReplyHandler(service *chatservices.AutoReplyService) *AutoReplyHandler {
	return &AutoReplyHandler{service: service}
}

// List handles GET /api/auto-reply.
func (h *AutoReplyHandler) List(c *gin.Context) {
	items, err := h.service.List()
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	out := make([]gin.H, 0, len(items))
	for _, a := range items {
		out = append(out, chatresponses.AutoReplyJSON(a))
	}
	helpers.Respond(c, http.StatusOK, "Auto replies retrieved successfully.", out)
}

// Add handles POST /api/auto-reply.
func (h *AutoReplyHandler) Add(c *gin.Context) {
	var req chatrequests.AutoReplySaveRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	reply, err := h.service.Add(req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Auto reply created successfully.", chatresponses.AutoReplyJSON(*reply))
}

// Update handles PUT /api/auto-reply/:id.
func (h *AutoReplyHandler) Update(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Auto reply not found.", nil)
		return
	}
	var req chatrequests.AutoReplySaveRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	reply, err := h.service.Update(id, req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Auto reply updated successfully.", chatresponses.AutoReplyJSON(*reply))
}

// Delete handles DELETE /api/auto-reply/:id.
func (h *AutoReplyHandler) Delete(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Auto reply not found.", nil)
		return
	}
	if err := h.service.Delete(id); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Auto reply deleted successfully.", nil)
}
