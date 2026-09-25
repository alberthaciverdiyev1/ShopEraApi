package routes

import (
	addresshandlers "shopera/internal/modules/address/handlers"
	addressrepositories "shopera/internal/modules/address/repositories"
	addressservices "shopera/internal/modules/address/services"
	deliveryrepositories "shopera/internal/modules/delivery/repositories"
	"shopera/internal/platform/module"
)

// Register wires the address module and mounts its routes.
func Register(deps module.Deps) {
	handler := addresshandlers.NewAddressHandler(
		addressservices.NewAddressService(addressrepositories.NewAddressRepository(deps.DB), deliveryrepositories.NewCityRepository(deps.DB)),
	)
	mount(deps.API, handler, deps.Auth)
}
