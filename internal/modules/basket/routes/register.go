package routes

import (
	baskethandlers "shopera/internal/modules/basket/handlers"
	"shopera/internal/platform/module"
)

// Register wires the basket module and mounts its routes.
func Register(deps module.Deps) {
	handler := baskethandlers.NewBasketHandler(deps.DB)
	mount(deps.API, handler, deps.Auth)
}
