// Package routes mounts the HelpAndPolicy module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	helphandlers "shopera/internal/modules/helpandpolicy/handlers"
)

// RegisterFaq mounts the faq routes on the given group.
func mountFaq(group *gin.RouterGroup, handler *helphandlers.FaqHandler, auth gin.HandlerFunc) {
	group.GET("/faq", handler.List)
	group.GET("/faq/admin", auth, handler.ListAdmin)
	group.POST("/faq", auth, handler.Add)
	group.PUT("/faq/:id", auth, handler.Update)
	group.DELETE("/faq/:id", auth, handler.Delete)
}
