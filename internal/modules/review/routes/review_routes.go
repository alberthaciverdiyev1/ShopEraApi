// Package routes mounts the Review module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	reviewhandlers "shopera/internal/modules/review/handlers"
)

// Register mounts the review routes on the given group.
func Register(group *gin.RouterGroup, handler *reviewhandlers.ReviewHandler, auth gin.HandlerFunc) {
	group.GET("/review/list-admin", auth, handler.ListAdmin)
	group.GET("/review/:product_id", handler.List)
	group.POST("/review", auth, handler.Add)
	group.PUT("/review/change-status", auth, handler.ChangeStatus)
	group.DELETE("/review/admin/:id", auth, handler.DeleteByAdmin)
	group.DELETE("/review/:id", auth, handler.Delete)
}
