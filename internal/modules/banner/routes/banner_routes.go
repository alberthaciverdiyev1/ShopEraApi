// Package routes mounts the Banner module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	bannerhandlers "shopera/internal/modules/banner/handlers"
)

// mount registers the banner routes on the given group.
func mount(group *gin.RouterGroup, handler *bannerhandlers.BannerHandler, auth gin.HandlerFunc, perm func(string) gin.HandlerFunc) {
	group.GET("/banner", handler.List)
	group.POST("/banner", auth, perm("add banner"), handler.Add)
	group.DELETE("/banner/:id", auth, perm("delete banner"), handler.Delete)
}
