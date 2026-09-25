package routes

import (
	popuphandlers "shopera/internal/modules/popup/handlers"
	popuprepositories "shopera/internal/modules/popup/repositories"
	popupservices "shopera/internal/modules/popup/services"
	"shopera/internal/platform/module"
)

// Register wires the popup module and mounts its routes.
func Register(deps module.Deps) {
	handler := popuphandlers.NewPopupHandler(
		popupservices.NewPopupService(popuprepositories.NewPopupRepository(deps.DB)),
	)
	mount(deps.API, handler, deps.Auth)
}
