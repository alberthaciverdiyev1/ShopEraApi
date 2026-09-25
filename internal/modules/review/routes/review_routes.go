// Package routes mounts the Review module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	reviewhandlers "shopera/internal/modules/review/handlers"
)

// mount registers the review routes on the given group.
func mount(group *gin.RouterGroup, handler *reviewhandlers.ReviewHandler, auth gin.HandlerFunc, perm func(string) gin.HandlerFunc) {
	group.GET("/review/list-admin", auth, handler.ListAdmin)
	group.GET("/review/:product_id", handler.List)
	group.POST("/review", auth, perm("add review"), handler.Add)
	group.PUT("/review/change-status", auth, handler.ChangeStatus)
	group.DELETE("/review/admin/:id", auth, perm("delete review"), handler.DeleteByAdmin)
	group.DELETE("/review/:id", auth, handler.Delete)
}
