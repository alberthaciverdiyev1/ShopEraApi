// Package routes mounts the Filter module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	filterhandlers "shopera/internal/modules/filter/handlers"
)

// Register mounts the filter routes on the given group.
func mount(group *gin.RouterGroup, handler *filterhandlers.FilterHandler, auth gin.HandlerFunc, perm func(string) gin.HandlerFunc) {
	// Public reads.
	group.GET("/filters", handler.List)
	group.GET("/category-filters", handler.CategoryFilters)

	// Admin CRUD.
	group.GET("/filter/:id", auth, handler.Details)
	group.POST("/filter", auth, perm("add filter"), handler.Add)
	group.PUT("/filter/:id", auth, perm("update filter"), handler.Update)
	group.DELETE("/filter/:id", auth, perm("delete filter"), handler.Delete)
	group.PUT("/filter/:id/categories", auth, perm("update filter"), handler.SetCategories)

	// Product filter values.
	group.GET("/product-filters", auth, handler.ProductValues)
	group.PUT("/product-filters", auth, perm("update product"), handler.SetProductValues)
}
