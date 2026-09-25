// Package routes mounts the Size module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	sizehandlers "shopera/internal/modules/size/handlers"
)

// mount registers the size routes (all require auth) on the given group.
func mount(group *gin.RouterGroup, handler *sizehandlers.SizeHandler, auth gin.HandlerFunc, perm func(string) gin.HandlerFunc) {
	sizeGroup := group.Group("/size", auth)
	sizeGroup.GET("", perm("view sizes"), handler.List)
	sizeGroup.POST("", perm("add size"), handler.Add)
	sizeGroup.GET("/:id", perm("details size"), handler.Details)
	sizeGroup.PUT("/:id", perm("update size"), handler.Update)
	sizeGroup.DELETE("/:id", perm("delete size"), handler.Delete)
}
