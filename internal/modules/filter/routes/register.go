package routes

import (
	filterhandlers "shopera/internal/modules/filter/handlers"
	filterrepositories "shopera/internal/modules/filter/repositories"
	filterservices "shopera/internal/modules/filter/services"
	"shopera/internal/platform/module"
)

// Register wires the filter module and mounts its routes.
func Register(deps module.Deps) {
	handler := filterhandlers.NewFilterHandler(
		filterservices.NewFilterService(filterrepositories.NewFilterRepository(deps.DB)),
	)
	mount(deps.API, handler, deps.Auth, deps.Permission)
}
