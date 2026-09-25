package routes

import (
	settinghandlers "shopera/internal/modules/setting/handlers"
	settingrepositories "shopera/internal/modules/setting/repositories"
	settingservices "shopera/internal/modules/setting/services"
	"shopera/internal/platform/module"
)

// Register wires the setting module and mounts its routes.
func Register(deps module.Deps) {
	handler := settinghandlers.NewSettingHandler(
		settingservices.NewSettingService(settingrepositories.NewSettingRepository(deps.DB)),
	)
	mount(deps.API, handler, deps.Auth)
}
