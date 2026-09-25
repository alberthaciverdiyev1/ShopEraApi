// Package routes mounts the Filter module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	filterhandlers "shopera/internal/modules/filter/handlers"
)

// Register mounts the filter routes on the given group.
func Register(group *gin.RouterGroup, handler *filterhandlers.FilterHandler) {
	group.GET("/filters", handler.List)
	group.GET("/category-filters", handler.CategoryFilters)
}
