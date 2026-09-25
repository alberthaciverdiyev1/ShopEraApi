package routes

import (
	addressrepositories "shopera/internal/modules/address/repositories"
	basketrepositories "shopera/internal/modules/basket/repositories"
	deliveryrepositories "shopera/internal/modules/delivery/repositories"
	orderhandlers "shopera/internal/modules/order/handlers"
	orderrepositories "shopera/internal/modules/order/repositories"
	orderservices "shopera/internal/modules/order/services"
	productrepositories "shopera/internal/modules/product/repositories"
	"shopera/internal/platform/module"
)

// Register wires the order module and mounts its routes.
func Register(deps module.Deps) {
	handler := orderhandlers.NewOrderHandler(orderservices.NewOrderService(
		orderrepositories.NewOrderRepository(deps.DB),
		basketrepositories.NewBasketRepository(deps.DB),
		productrepositories.NewProductRepository(deps.DB),
		addressrepositories.NewAddressRepository(deps.DB),
		deliveryrepositories.NewCityRepository(deps.DB),
		deliveryrepositories.NewDeliveryPriceRepository(deps.DB),
		deliveryrepositories.NewPickupPointRepository(deps.DB),
	))
	mount(deps.API, handler, deps.Auth)
}
