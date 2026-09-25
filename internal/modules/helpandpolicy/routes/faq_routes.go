// Package routes mounts the HelpAndPolicy module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	helphandlers "shopera/internal/modules/helpandpolicy/handlers"
)

// mountFaq registers the faq routes on the given group.
func mountFaq(group *gin.RouterGroup, handler *helphandlers.FaqHandler, auth gin.HandlerFunc, perm func(string) gin.HandlerFunc) {
	group.GET("/faq", handler.List)
	group.GET("/faq/admin", auth, handler.ListAdmin)
	group.POST("/faq", auth, perm("add faq"), handler.Add)
	group.PUT("/faq/:id", auth, perm("update faq"), handler.Update)
	group.DELETE("/faq/:id", auth, perm("delete faq"), handler.Delete)
}
