// Package requests holds RoleAndPermissions request payloads.
package requests

// NameRequest carries a role or permission name.
type NameRequest struct {
	Name string `json:"name" binding:"required,max=255"`
}

// PermissionNameRequest names a permission to assign/revoke.
type PermissionNameRequest struct {
	Permission string `json:"permission" binding:"required"`
}

// RoleNameRequest names a role to assign/revoke.
type RoleNameRequest struct {
	Role string `json:"role" binding:"required"`
}
