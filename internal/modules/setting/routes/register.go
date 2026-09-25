package routes

import (
	productrepositories "shopera/internal/modules/product/repositories"
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
	stats := settinghandlers.NewStatisticHandler(
		settingservices.NewStatisticService(
			settingrepositories.NewStatisticRepository(deps.DB),
			productrepositories.NewProductRepository(deps.DB),
		),
	)
	mount(deps.API, handler, stats, deps.Auth)
}
