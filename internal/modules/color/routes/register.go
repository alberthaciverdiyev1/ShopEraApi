package routes

import (
	colorhandlers "shopera/internal/modules/color/handlers"
	colorrepositories "shopera/internal/modules/color/repositories"
	colorservices "shopera/internal/modules/color/services"
	"shopera/internal/platform/module"
)

// Register wires the color module and mounts its routes.
func Register(deps module.Deps) {
	handler := colorhandlers.NewColorHandler(
		colorservices.NewColorService(colorrepositories.NewColorRepository(deps.DB)),
	)
	mount(deps.API, handler, deps.Auth)
}
