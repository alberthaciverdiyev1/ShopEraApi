// Package routes mounts the Favorite module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	favoritehandlers "shopera/internal/modules/favorite/handlers"
)

// Register mounts the favorite routes (all require auth) on the given group.
func mount(group *gin.RouterGroup, handler *favoritehandlers.FavoriteHandler, auth gin.HandlerFunc) {
	favoriteGroup := group.Group("/favorite", auth)
	favoriteGroup.GET("", handler.List)
	favoriteGroup.POST("/:id", handler.Add)
	favoriteGroup.DELETE("/:id", handler.Delete)
}
