package routes

import (
	balancerepositories "shopera/internal/modules/balance/repositories"
	rolepermissionrepositories "shopera/internal/modules/rolepermission/repositories"
	settingrepositories "shopera/internal/modules/setting/repositories"
	settingservices "shopera/internal/modules/setting/services"
	userhandlers "shopera/internal/modules/user/handlers"
	userrepositories "shopera/internal/modules/user/repositories"
	userservices "shopera/internal/modules/user/services"
	"shopera/internal/platform/module"
)

// Register wires the user module and mounts its routes.
func Register(deps module.Deps) {
	users := userrepositories.NewUserRepository(deps.DB)
	roles := rolepermissionrepositories.NewRoleRepository(deps.DB)

	handler := userhandlers.NewUserHandler(
		userservices.NewUserService(users, userrepositories.NewOtpRepository(deps.DB)),
	)
	admin := userhandlers.NewAdminUserHandler(
		userservices.NewAdminUserService(
			users,
			userrepositories.NewRefreshTokenRepository(deps.DB),
			roles,
			balancerepositories.NewBalanceRepository(deps.DB),
			settingservices.NewSettingService(settingrepositories.NewSettingRepository(deps.DB)),
		),
	)

	mount(deps.API, handler, admin, deps.Auth, deps.Permission)
}
