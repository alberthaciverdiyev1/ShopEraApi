package routes

import (
	addressrepositories "shopera/internal/modules/address/repositories"
	basketrepositories "shopera/internal/modules/basket/repositories"
	deliveryrepositories "shopera/internal/modules/delivery/repositories"
	productrepositories "shopera/internal/modules/product/repositories"
	promocodehandlers "shopera/internal/modules/promocode/handlers"
	promocoderepositories "shopera/internal/modules/promocode/repositories"
	promocodeservices "shopera/internal/modules/promocode/services"
	"shopera/internal/platform/module"
)

// Register wires the promocode module and mounts its routes.
func Register(deps module.Deps) {
	handler := promocodehandlers.NewPromoCodeHandler(promocodeservices.NewPromoCodeService(
		promocoderepositories.NewPromoCodeRepository(deps.DB),
		addressrepositories.NewAddressRepository(deps.DB),
		deliveryrepositories.NewCityRepository(deps.DB),
		deliveryrepositories.NewDeliveryPriceRepository(deps.DB),
		basketrepositories.NewBasketRepository(deps.DB),
		productrepositories.NewProductRepository(deps.DB),
	))
	mount(deps.API, handler, deps.Auth, deps.Permission)
}
