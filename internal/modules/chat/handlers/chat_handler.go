// Package handlers holds Chat module HTTP handlers.
package handlers

import (
	"net/http"
	"strconv"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	chatrequests "shopera/internal/modules/chat/requests"
	chatresponses "shopera/internal/modules/chat/responses"
	chatservices "shopera/internal/modules/chat/services"
)

// ChatHandler serves the chat endpoints.
type ChatHandler struct {
	service *chatservices.ChatService
}

func NewChatHandler(service *chatservices.ChatService) *ChatHandler {
	return &ChatHandler{service: service}
}

// List handles GET /api/chat.
func (h *ChatHandler) List(c *gin.Context) {
	items, err := h.service.MessageList(c.GetInt64("userID"))
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Messages retrieved successfully", items)
}

// Send handles POST /api/chat/send (multipart or JSON).
func (h *ChatHandler) Send(c *gin.Context) {
	var req chatrequests.SendRequest
	if err := c.ShouldBind(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}

	var imagePath *string
	if form, err := c.MultipartForm(); err == nil {
		if _, ok := form.File["image"]; ok {
			if path, err := helpers.SaveUpload(c, "image", "chat/attachments"); err == nil {
				imagePath = &path
			}
		}
	}

	if err := h.service.Send(c.Request.Context(), c.GetInt64("userID"), req, imagePath); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Message sent", nil)
}

// Messages handles GET /api/chat/conversation/:conversationId.
func (h *ChatHandler) Messages(c *gin.Context) {
	id, err := paramID(c, "conversationId")
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Conversation not found.", nil)
		return
	}
	items, err := h.service.Messages(id)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Messages retrieved successfully.", items)
}

// MarkAsRead handles POST /api/chat/conversation/read/:conversationId.
func (h *ChatHandler) MarkAsRead(c *gin.Context) {
	id, err := paramID(c, "conversationId")
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Conversation not found.", nil)
		return
	}
	if err := h.service.MarkAsRead(id); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Conversation marked as read successfully.", nil)
}

// Delete handles DELETE /api/chat/message/:messageId.
func (h *ChatHandler) Delete(c *gin.Context) {
	id, err := paramID(c, "messageId")
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Message not found.", nil)
		return
	}
	if err := h.service.DeleteMessage(id); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Message deleted successfully.", nil)
}

// DeleteConversation handles DELETE /api/chat/conversation/:conversationId.
func (h *ChatHandler) DeleteConversation(c *gin.Context) {
	id, err := paramID(c, "conversationId")
	if err != nil {
		helpers.Respond(c, http.StatusOK, "Conversation already deleted.", nil)
		return
	}
	if err := h.service.DeleteConversation(id); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Conversation deleted successfully.", nil)
}

// ConversationList handles GET /api/chat/conversation.
func (h *ChatHandler) ConversationList(c *gin.Context) {
	result, err := h.service.ConversationList(helpers.ParseQuery(c))
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Conversations retrieved successfully.", result)
}

func paramID(c *gin.Context, name string) (int64, error) {
	return strconv.ParseInt(c.Param(name), 10, 64)
}

var _ = chatresponses.MessageJSON
