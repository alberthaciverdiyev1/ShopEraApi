// Package routes mounts the Basket module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	baskethandlers "shopera/internal/modules/basket/handlers"
)

// Register mounts the basket routes (all require auth) on the given group.
func Register(group *gin.RouterGroup, handler *baskethandlers.BasketHandler, auth gin.HandlerFunc) {
	basketGroup := group.Group("/basket", auth)
	basketGroup.GET("", handler.List)
	basketGroup.POST("", handler.Add)
	basketGroup.PUT("/:id", handler.Update)
	basketGroup.DELETE("/:id", handler.Delete)
}
