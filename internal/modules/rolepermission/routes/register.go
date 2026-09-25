package routes

import (
	rolehandlers "shopera/internal/modules/rolepermission/handlers"
	rolerepositories "shopera/internal/modules/rolepermission/repositories"
	roleservices "shopera/internal/modules/rolepermission/services"
	"shopera/internal/platform/module"
)

// Register wires the RoleAndPermissions module and mounts its routes.
func Register(deps module.Deps) {
	repo := rolerepositories.NewRoleRepository(deps.DB)
	roleHandler := rolehandlers.NewRoleHandler(roleservices.NewRoleService(repo))
	permissionHandler := rolehandlers.NewPermissionHandler(roleservices.NewPermissionService(repo))
	mount(deps.API, roleHandler, permissionHandler, deps.Auth, deps.Permission)
}
