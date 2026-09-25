// Package routes mounts the Setting module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	settinghandlers "shopera/internal/modules/setting/handlers"
)

// mount registers the setting routes on the given group.
func mount(group *gin.RouterGroup, handler *settinghandlers.SettingHandler, stats *settinghandlers.StatisticHandler, auth gin.HandlerFunc, perm func(string) gin.HandlerFunc) {
	group.GET("/setting", handler.List)
	group.PUT("/setting", auth, perm("update setting"), handler.Update)
	group.POST("/change-locale", handler.ChangeLocale)
	group.GET("/global-statistics", stats.Statistics)
}
