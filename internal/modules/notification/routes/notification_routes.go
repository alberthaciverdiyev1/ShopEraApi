// Package routes mounts the Notification module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	notificationhandlers "shopera/internal/modules/notification/handlers"
)

// mount registers the notification routes on the given group.
func mount(group *gin.RouterGroup, handler *notificationhandlers.NotificationHandler, tokenHandler *notificationhandlers.NotificationTokenHandler, auth gin.HandlerFunc, perm func(string) gin.HandlerFunc) {
	group.POST("/notification", auth, perm("send notification"), handler.Send)
	group.GET("/notification", auth, perm("view notifications"), handler.List)
	group.GET("/notification/admin", auth, handler.ListAdmin)
	group.DELETE("/notification/:id", auth, handler.Delete)
	group.POST("/notification/save-token", tokenHandler.SaveToken)
}
