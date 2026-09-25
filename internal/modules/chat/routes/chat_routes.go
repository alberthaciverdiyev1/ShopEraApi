// Package routes mounts the Chat module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	chathandlers "shopera/internal/modules/chat/handlers"
)

// mount registers the chat routes (all require auth) on the given group.
func mount(group *gin.RouterGroup, handler *chathandlers.ChatHandler, autoReply *chathandlers.AutoReplyHandler, auth gin.HandlerFunc, perm func(string) gin.HandlerFunc) {
	fullChat := perm("full chat access")

	group.GET("/chat", auth, fullChat, handler.List)
	group.POST("/chat/send", auth, fullChat, handler.Send)
	group.DELETE("/chat/message/:messageId", auth, fullChat, handler.Delete)
	group.GET("/chat/conversation", auth, fullChat, handler.ConversationList)
	group.DELETE("/chat/conversation/:conversationId", auth, fullChat, handler.DeleteConversation)
	group.GET("/chat/conversation/:conversationId", auth, fullChat, handler.Messages)
	group.POST("/chat/conversation/read/:conversationId", auth, fullChat, handler.MarkAsRead)

	group.GET("/auto-reply", auth, fullChat, autoReply.List)
	group.POST("/auto-reply", auth, fullChat, autoReply.Add)
	group.PUT("/auto-reply/:id", auth, fullChat, autoReply.Update)
	group.DELETE("/auto-reply/:id", auth, fullChat, autoReply.Delete)
}
