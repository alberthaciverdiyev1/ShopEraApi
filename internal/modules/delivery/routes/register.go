package routes

import (
	deliveryhandlers "shopera/internal/modules/delivery/handlers"
	deliveryrepositories "shopera/internal/modules/delivery/repositories"
	deliveryservices "shopera/internal/modules/delivery/services"
	"shopera/internal/platform/module"
)

// Register wires the delivery module and mounts its routes.
func Register(deps module.Deps) {
	cityRepo := deliveryrepositories.NewCityRepository(deps.DB)
	cityHandler := deliveryhandlers.NewCityHandler(deliveryservices.NewCityService(cityRepo))
	priceHandler := deliveryhandlers.NewDeliveryPriceHandler(
		deliveryservices.NewDeliveryPriceService(deliveryrepositories.NewDeliveryPriceRepository(deps.DB), cityRepo),
	)
	pickupHandler := deliveryhandlers.NewPickupPointHandler(
		deliveryservices.NewPickupPointService(deliveryrepositories.NewPickupPointRepository(deps.DB)),
	)
	infoHandler := deliveryhandlers.NewDeliveryInfoHandler(
		deliveryservices.NewDeliveryInfoService(deliveryrepositories.NewDeliveryInfoRepository(deps.DB)),
	)
	mount(deps.API, cityHandler, priceHandler, pickupHandler, infoHandler, deps.Auth)
}
