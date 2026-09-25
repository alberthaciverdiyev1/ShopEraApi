package routes

import (
	authhandlers "shopera/internal/modules/auth/handlers"
	authservices "shopera/internal/modules/auth/services"
	rolepermissionrepositories "shopera/internal/modules/rolepermission/repositories"
	userrepositories "shopera/internal/modules/user/repositories"
	userservices "shopera/internal/modules/user/services"
	"shopera/internal/platform/module"
)

// Register wires the auth module and mounts its routes.
func Register(deps module.Deps) {
	users := userrepositories.NewUserRepository(deps.DB)
	authService := authservices.NewAuthService(
		users,
		rolepermissionrepositories.NewRoleRepository(deps.DB),
		userrepositories.NewRefreshTokenRepository(deps.DB),
		deps.Cfg,
	)
	handler := authhandlers.NewAuthHandler(authService)

	otpService := userservices.NewOtpService(userrepositories.NewOtpRepository(deps.DB), userservices.NewLsimClient())
	passwordResetService := userservices.NewPasswordResetService(
		users,
		userrepositories.NewOtpRepository(deps.DB),
		userrepositories.NewPasswordResetRepository(deps.DB),
		userrepositories.NewRefreshTokenRepository(deps.DB),
		userservices.LogMailer{},
	)
	recovery := authhandlers.NewAuthRecoveryHandler(
		authservices.NewAuthRecoveryService(otpService, passwordResetService, users, authService, deps.Cfg.App.Name),
	)

	mount(deps.API, handler, recovery, deps.Auth, deps.Permission)
}
