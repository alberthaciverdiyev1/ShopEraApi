// Package routes mounts the Size module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	sizehandlers "shopera/internal/modules/size/handlers"
)

// Register mounts the size routes (all require auth) on the given group.
func Register(group *gin.RouterGroup, handler *sizehandlers.SizeHandler, auth gin.HandlerFunc) {
	sizeGroup := group.Group("/size", auth)
	sizeGroup.GET("", handler.List)
	sizeGroup.POST("", handler.Add)
	sizeGroup.GET("/:id", handler.Details)
	sizeGroup.PUT("/:id", handler.Update)
	sizeGroup.DELETE("/:id", handler.Delete)
}
