// Package routes mounts the Color module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	colorhandlers "shopera/internal/modules/color/handlers"
)

// mount registers the color routes (all require auth) on the given group.
func mount(group *gin.RouterGroup, handler *colorhandlers.ColorHandler, auth gin.HandlerFunc, perm func(string) gin.HandlerFunc) {
	colorGroup := group.Group("/color", auth)
	colorGroup.GET("", perm("view colors"), handler.List)
	colorGroup.POST("", perm("add color"), handler.Add)
	colorGroup.GET("/:id", perm("details color"), handler.Details)
	colorGroup.PUT("/:id", perm("update color"), handler.Update)
	colorGroup.DELETE("/:id", perm("delete color"), handler.Delete)
}
