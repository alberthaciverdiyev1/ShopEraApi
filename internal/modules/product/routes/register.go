package routes

import (
	producthandlers "shopera/internal/modules/product/handlers"
	productrepositories "shopera/internal/modules/product/repositories"
	productservices "shopera/internal/modules/product/services"
	settingrepositories "shopera/internal/modules/setting/repositories"
	settingservices "shopera/internal/modules/setting/services"
	"shopera/internal/platform/module"
)

// Register wires the product module and mounts its routes.
func Register(deps module.Deps) {
	settingService := settingservices.NewSettingService(settingrepositories.NewSettingRepository(deps.DB))
	handler := producthandlers.NewProductHandler(
		productservices.NewProductService(productrepositories.NewProductRepository(deps.DB), settingService),
	)
	mount(deps.API, handler, deps.Auth, deps.Permission)
}
