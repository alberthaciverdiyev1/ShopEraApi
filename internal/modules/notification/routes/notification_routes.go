// Package routes mounts the Notification module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	notificationhandlers "shopera/internal/modules/notification/handlers"
)

// Register mounts the notification routes on the given group.
func mount(group *gin.RouterGroup, handler *notificationhandlers.NotificationHandler, tokenHandler *notificationhandlers.NotificationTokenHandler, auth gin.HandlerFunc) {
	group.POST("/notification", auth, handler.Send)
	group.GET("/notification", auth, handler.List)
	group.GET("/notification/admin", auth, handler.ListAdmin)
	group.DELETE("/notification/:id", auth, handler.Delete)
	group.POST("/notification/save-token", tokenHandler.SaveToken)
}
