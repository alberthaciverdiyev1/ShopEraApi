// Package routes mounts the Category module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	categoryhandlers "shopera/internal/modules/category/handlers"
)

// Register mounts the category routes on the given group.
func Register(group *gin.RouterGroup, handler *categoryhandlers.CategoryHandler, auth gin.HandlerFunc) {
	categoryGroup := group.Group("/category")
	categoryGroup.GET("", handler.List)
	categoryGroup.GET("/admin", handler.ListAdmin)
	categoryGroup.GET("/:id", auth, handler.Details)
	categoryGroup.POST("", auth, handler.Add)
	categoryGroup.PUT("/:id", auth, handler.Update)
	categoryGroup.DELETE("/:id", auth, handler.Delete)
}
