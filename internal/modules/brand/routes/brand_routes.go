// Package routes mounts the Brand module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	brandhandlers "shopera/internal/modules/brand/handlers"
)

// mount registers the brand routes on the given group.
func mount(group *gin.RouterGroup, handler *brandhandlers.BrandHandler, auth gin.HandlerFunc, perm func(string) gin.HandlerFunc) {
	brandGroup := group.Group("/brand")
	brandGroup.GET("", handler.List)
	brandGroup.GET("/:id", handler.Details)
	brandGroup.POST("", auth, perm("add brand"), handler.Add)
	brandGroup.PUT("/:id", auth, perm("update brand"), handler.Update)
	brandGroup.DELETE("/:id", auth, perm("delete brand"), handler.Delete)
}
