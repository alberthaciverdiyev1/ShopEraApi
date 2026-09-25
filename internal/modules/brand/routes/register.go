package routes

import (
	brandhandlers "shopera/internal/modules/brand/handlers"
	brandrepositories "shopera/internal/modules/brand/repositories"
	brandservices "shopera/internal/modules/brand/services"
	"shopera/internal/platform/module"
)

// Register wires the brand module and mounts its routes.
func Register(deps module.Deps) {
	handler := brandhandlers.NewBrandHandler(
		brandservices.NewBrandService(brandrepositories.NewBrandRepository(deps.DB)),
	)
	mount(deps.API, handler, deps.Auth)
}
