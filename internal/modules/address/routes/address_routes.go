// Package routes mounts the Address module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	addresshandlers "shopera/internal/modules/address/handlers"
)

// Register mounts the address routes (all require auth) on the given group.
func Register(group *gin.RouterGroup, handler *addresshandlers.AddressHandler, auth gin.HandlerFunc) {
	addressGroup := group.Group("/user/address", auth)
	addressGroup.GET("", handler.List)
	addressGroup.POST("", handler.Add)
	addressGroup.GET("/:id", handler.Details)
	addressGroup.PUT("/:id", handler.Update)
	addressGroup.DELETE("/:id", handler.Delete)
}
