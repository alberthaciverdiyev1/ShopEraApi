// Package routes mounts the User module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	userhandlers "shopera/internal/modules/user/handlers"
)

// mount registers the user routes on the given group.
func mount(
	group *gin.RouterGroup,
	handler *userhandlers.UserHandler,
	admin *userhandlers.AdminUserHandler,
	auth gin.HandlerFunc,
	perm func(string) gin.HandlerFunc,
) {
	userGroup := group.Group("/user")
	updateUser := perm("update user")

	// Profile (self, optional user_id for admins).
	userGroup.PUT("/change-email", auth, updateUser, handler.ChangeEmail)
	userGroup.PUT("/change-name", auth, updateUser, handler.ChangeName)
	userGroup.PUT("/change-surname", auth, updateUser, handler.ChangeSurname)
	userGroup.PUT("/change-phone", auth, updateUser, handler.ChangePhone)

	// Management.
	userGroup.GET("/list", auth, perm("view users"), admin.List)
	userGroup.GET("/details", auth, perm("details user"), admin.Details)
	userGroup.GET("/details/:id", auth, perm("details user"), admin.Details)
	userGroup.POST("/block", auth, admin.Block)
	userGroup.PUT("/wholesaler-status", auth, updateUser, admin.ChangeWholesalerStatus)
	userGroup.DELETE("/delete-admin/:id", auth, admin.Delete)
	userGroup.DELETE("/delete", auth, admin.DeleteMyAccount)
}
