// Package routes mounts the RoleAndPermissions routes.
package routes

import (
	"github.com/gin-gonic/gin"

	rolehandlers "shopera/internal/modules/rolepermission/handlers"
)

// mount registers the role and permission routes on the given group.
func mount(group *gin.RouterGroup, role *rolehandlers.RoleHandler, permission *rolehandlers.PermissionHandler, auth gin.HandlerFunc) {
	// Static paths first so they are not shadowed by /:id.
	group.POST("/role/assign-role/:userId", auth, role.AssignRoleToUser)
	group.POST("/role/revoke-role/:userId", auth, role.RevokeRoleFromUser)
	group.POST("/role/:role/give-permission", auth, role.GivePermission)
	group.POST("/role/:role/revoke-permission", auth, role.RevokePermission)

	group.GET("/role", auth, role.GetAll)
	group.POST("/role", auth, role.Add)
	group.GET("/role/:id", auth, role.Details)
	group.PUT("/role/:role", auth, role.Update)
	group.DELETE("/role/:role", auth, role.Delete)

	group.GET("/permission", auth, permission.GetAll)
	group.POST("/permission", auth, permission.Store)
	group.GET("/permission/:id", auth, permission.Show)
	group.PUT("/permission/:permission", auth, permission.Update)
	group.DELETE("/permission/:permission", auth, permission.Delete)
}
