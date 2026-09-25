// Package routes mounts the Category module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	categoryhandlers "shopera/internal/modules/category/handlers"
)

// mount registers the category routes on the given group.
func mount(group *gin.RouterGroup, handler *categoryhandlers.CategoryHandler, auth gin.HandlerFunc, perm func(string) gin.HandlerFunc) {
	categoryGroup := group.Group("/category")
	categoryGroup.GET("", handler.List)
	categoryGroup.GET("/with-products", handler.WithProducts)
	categoryGroup.GET("/admin", handler.ListAdmin)
	categoryGroup.GET("/:id", auth, perm("details category"), handler.Details)
	categoryGroup.POST("", auth, perm("add category"), handler.Add)
	categoryGroup.PUT("/:id", auth, perm("update category"), handler.Update)
	categoryGroup.DELETE("/:id", auth, perm("delete category"), handler.Delete)
}
