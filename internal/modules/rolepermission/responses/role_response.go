// Package responses holds RoleAndPermissions API response shapes.
package responses

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/modules/rolepermission/models"
)

// PermissionJSON maps a permission.
func PermissionJSON(p models.Permission) gin.H {
	return gin.H{"id": p.ID, "name": p.Name, "guard_name": p.GuardName}
}

// PermissionCollection maps permissions.
func PermissionCollection(items []models.Permission) []gin.H {
	out := make([]gin.H, 0, len(items))
	for _, p := range items {
		out = append(out, PermissionJSON(p))
	}
	return out
}

// RoleJSON maps a role, optionally with its permissions.
func RoleJSON(r models.Role, permissions []models.Permission) gin.H {
	out := gin.H{"id": r.ID, "name": r.Name, "guard_name": r.GuardName}
	if permissions != nil {
		out["permissions"] = PermissionCollection(permissions)
	}
	return out
}
