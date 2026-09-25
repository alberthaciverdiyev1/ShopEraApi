package routes

import (
	authhandlers "shopera/internal/modules/auth/handlers"
	authservices "shopera/internal/modules/auth/services"
	rolepermissionrepositories "shopera/internal/modules/rolepermission/repositories"
	userrepositories "shopera/internal/modules/user/repositories"
	"shopera/internal/platform/module"
)

// Register wires the auth module and mounts its routes.
func Register(deps module.Deps) {
	handler := authhandlers.NewAuthHandler(
		authservices.NewAuthService(userrepositories.NewUserRepository(deps.DB), rolepermissionrepositories.NewRoleRepository(deps.DB), deps.Cfg),
	)
	mount(deps.API, handler, deps.Auth)
}
