// Package routes mounts the User module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	userhandlers "shopera/internal/modules/user/handlers"
)

// mount registers the user routes on the given group.
func mount(group *gin.RouterGroup, handler *userhandlers.UserHandler, admin *userhandlers.AdminUserHandler, auth gin.HandlerFunc) {
	userGroup := group.Group("/user")

	// Profile (self, optional user_id for admins).
	userGroup.PUT("/change-email", auth, handler.ChangeEmail)
	userGroup.PUT("/change-name", auth, handler.ChangeName)
	userGroup.PUT("/change-surname", auth, handler.ChangeSurname)
	userGroup.PUT("/change-phone", auth, handler.ChangePhone)

	// Management.
	userGroup.GET("/list", auth, admin.List)
	userGroup.GET("/details", auth, admin.Details)
	userGroup.GET("/details/:id", auth, admin.Details)
	userGroup.POST("/block", auth, admin.Block)
	userGroup.PUT("/wholesaler-status", auth, admin.ChangeWholesalerStatus)
	userGroup.DELETE("/delete-admin/:id", auth, admin.Delete)
	userGroup.DELETE("/delete", auth, admin.DeleteMyAccount)
}
