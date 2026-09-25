package routes

import (
	userhandlers "shopera/internal/modules/user/handlers"
	userrepositories "shopera/internal/modules/user/repositories"
	userservices "shopera/internal/modules/user/services"
	"shopera/internal/platform/module"
)

// Register wires the user module and mounts its routes.
func Register(deps module.Deps) {
	handler := userhandlers.NewUserHandler(
		userservices.NewUserService(
			userrepositories.NewUserRepository(deps.DB),
			userrepositories.NewOtpRepository(deps.DB),
		),
	)
	mount(deps.API, handler, deps.Auth)
}
