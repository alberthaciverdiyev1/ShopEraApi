// Package routes mounts the Banner module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	bannerhandlers "shopera/internal/modules/banner/handlers"
)

// Register mounts the banner routes on the given group.
func Register(group *gin.RouterGroup, handler *bannerhandlers.BannerHandler, auth gin.HandlerFunc) {
	group.GET("/banner", handler.List)
	group.POST("/banner", auth, handler.Add)
	group.DELETE("/banner/:id", auth, handler.Delete)
}
