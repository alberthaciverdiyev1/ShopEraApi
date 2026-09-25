// Package routes mounts the Setting module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	settinghandlers "shopera/internal/modules/setting/handlers"
)

// Register mounts the setting routes on the given group.
func Register(group *gin.RouterGroup, handler *settinghandlers.SettingHandler, auth gin.HandlerFunc) {
	group.GET("/setting", handler.List)
	group.PUT("/setting", auth, handler.Update)
	group.POST("/change-locale", handler.ChangeLocale)
}
