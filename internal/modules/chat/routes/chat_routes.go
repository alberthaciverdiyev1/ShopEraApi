// Package routes mounts the Chat module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	chathandlers "shopera/internal/modules/chat/handlers"
)

// Register mounts the chat routes (all require auth) on the given group.
func mount(group *gin.RouterGroup, handler *chathandlers.ChatHandler, autoReply *chathandlers.AutoReplyHandler, auth gin.HandlerFunc) {
	group.GET("/chat", auth, handler.List)
	group.POST("/chat/send", auth, handler.Send)
	group.DELETE("/chat/message/:messageId", auth, handler.Delete)
	group.GET("/chat/conversation", auth, handler.ConversationList)
	group.DELETE("/chat/conversation/:conversationId", auth, handler.DeleteConversation)
	group.GET("/chat/conversation/:conversationId", auth, handler.Messages)
	group.POST("/chat/conversation/read/:conversationId", auth, handler.MarkAsRead)

	group.GET("/auto-reply", auth, autoReply.List)
	group.POST("/auto-reply", auth, autoReply.Add)
	group.PUT("/auto-reply/:id", auth, autoReply.Update)
	group.DELETE("/auto-reply/:id", auth, autoReply.Delete)
}
