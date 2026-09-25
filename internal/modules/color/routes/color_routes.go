// Package routes mounts the Color module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	colorhandlers "shopera/internal/modules/color/handlers"
)

// Register mounts the color routes (all require auth) on the given group.
func mount(group *gin.RouterGroup, handler *colorhandlers.ColorHandler, auth gin.HandlerFunc) {
	colorGroup := group.Group("/color", auth)
	colorGroup.GET("", handler.List)
	colorGroup.POST("", handler.Add)
	colorGroup.GET("/:id", handler.Details)
	colorGroup.PUT("/:id", handler.Update)
	colorGroup.DELETE("/:id", handler.Delete)
}
