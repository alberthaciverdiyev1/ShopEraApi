// Package routes mounts the RoleAndPermissions routes.
package routes

import (
	"github.com/gin-gonic/gin"

	rolehandlers "shopera/internal/modules/rolepermission/handlers"
)

// mount registers the role and permission routes on the given group.
func mount(
	group *gin.RouterGroup,
	role *rolehandlers.RoleHandler,
	permission *rolehandlers.PermissionHandler,
	auth gin.HandlerFunc,
	perm func(string) gin.HandlerFunc,
) {
	manageRoles := perm("manage-roles")
	managePermissions := perm("manage-permissions")

	// Static paths first so they are not shadowed by /:id.
	group.POST("/role/assign-role/:userId", auth, manageRoles, role.AssignRoleToUser)
	group.POST("/role/revoke-role/:userId", auth, manageRoles, role.RevokeRoleFromUser)
	group.POST("/role/:role/give-permission", auth, manageRoles, role.GivePermission)
	group.POST("/role/:role/revoke-permission", auth, manageRoles, role.RevokePermission)

	group.GET("/role", auth, manageRoles, role.GetAll)
	group.POST("/role", auth, manageRoles, role.Add)
	group.GET("/role/:id", auth, manageRoles, role.Details)
	group.PUT("/role/:role", auth, manageRoles, role.Update)
	group.DELETE("/role/:role", auth, manageRoles, role.Delete)

	group.GET("/permission", auth, managePermissions, permission.GetAll)
	group.POST("/permission", auth, managePermissions, permission.Store)
	group.GET("/permission/:id", auth, managePermissions, permission.Show)
	group.PUT("/permission/:permission", auth, managePermissions, permission.Update)
	group.DELETE("/permission/:permission", auth, managePermissions, permission.Delete)
}
