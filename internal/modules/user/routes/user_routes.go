// Package routes mounts the User module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	userhandlers "shopera/internal/modules/user/handlers"
)

// mount registers the user routes on the given group.
func mount(group *gin.RouterGroup, handler *userhandlers.UserHandler, auth gin.HandlerFunc) {
	userGroup := group.Group("/user")
	userGroup.PUT("/change-email", auth, handler.ChangeEmail)
	userGroup.PUT("/change-name", auth, handler.ChangeName)
	userGroup.PUT("/change-surname", auth, handler.ChangeSurname)
	userGroup.PUT("/change-phone", auth, handler.ChangePhone)
}
