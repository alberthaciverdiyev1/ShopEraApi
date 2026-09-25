package routes

import (
	sizehandlers "shopera/internal/modules/size/handlers"
	sizerepositories "shopera/internal/modules/size/repositories"
	sizeservices "shopera/internal/modules/size/services"
	"shopera/internal/platform/module"
)

// Register wires the size module and mounts its routes.
func Register(deps module.Deps) {
	handler := sizehandlers.NewSizeHandler(
		sizeservices.NewSizeService(sizerepositories.NewSizeRepository(deps.DB)),
	)
	mount(deps.API, handler, deps.Auth, deps.Permission)
}
