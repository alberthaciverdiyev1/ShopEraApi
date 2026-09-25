// Package routes mounts the Delivery module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	cityhandlers "shopera/internal/modules/delivery/handlers"
)

// Register mounts the location routes on the given group.
func Register(group *gin.RouterGroup, handler *cityhandlers.CityHandler) {
	group.GET("/cities", handler.List)
}
