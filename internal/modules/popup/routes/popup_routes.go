// Package routes mounts the Popup module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	popuphandlers "shopera/internal/modules/popup/handlers"
)

// mount registers the popup routes on the given group.
func mount(group *gin.RouterGroup, handler *popuphandlers.PopupHandler, auth gin.HandlerFunc, perm func(string) gin.HandlerFunc) {
	group.GET("/popup", handler.List)
	group.GET("/popup/show-one", handler.ShowOne)
	group.POST("/popup", auth, perm("add popup"), handler.Add)
	group.PUT("/popup/:id", auth, perm("active popup"), handler.ShowHome)
	group.DELETE("/popup/:id", auth, perm("delete popup"), handler.Delete)
}
