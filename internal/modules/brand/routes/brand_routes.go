// Package routes mounts the Brand module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	brandhandlers "shopera/internal/modules/brand/handlers"
)

// Register mounts the brand routes on the given group.
func Register(group *gin.RouterGroup, handler *brandhandlers.BrandHandler, auth gin.HandlerFunc) {
	brandGroup := group.Group("/brand")
	brandGroup.GET("", handler.List)
	brandGroup.GET("/:id", handler.Details)
	brandGroup.POST("", auth, handler.Add)
	brandGroup.PUT("/:id", auth, handler.Update)
	brandGroup.DELETE("/:id", auth, handler.Delete)
}
