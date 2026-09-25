// Package routes mounts the Popup module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	popuphandlers "shopera/internal/modules/popup/handlers"
)

// Register mounts the popup routes on the given group.
func Register(group *gin.RouterGroup, handler *popuphandlers.PopupHandler, auth gin.HandlerFunc) {
	group.GET("/popup", handler.List)
	group.GET("/popup/show-one", handler.ShowOne)
	group.POST("/popup", auth, handler.Add)
	group.PUT("/popup/:id", auth, handler.ShowHome)
	group.DELETE("/popup/:id", auth, handler.Delete)
}
