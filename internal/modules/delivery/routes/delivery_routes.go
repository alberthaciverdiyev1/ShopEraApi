// Package routes mounts the Delivery module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	deliveryhandlers "shopera/internal/modules/delivery/handlers"
)

// mount registers every delivery-related route on the given group.
func mount(
	group *gin.RouterGroup,
	city *deliveryhandlers.CityHandler,
	prices *deliveryhandlers.DeliveryPriceHandler,
	pickup *deliveryhandlers.PickupPointHandler,
	info *deliveryhandlers.DeliveryInfoHandler,
	auth gin.HandlerFunc,
	perm func(string) gin.HandlerFunc,
) {
	// Cities with their towns (app city picker).
	group.GET("/cities", city.List)
	// Active city options.
	group.GET("/city", prices.Cities)

	// Delivery prices.
	group.GET("/delivery", auth, perm("view deliveries"), prices.List)
	group.POST("/delivery", auth, perm("add delivery"), prices.Add)
	group.GET("/delivery/details", auth, perm("details delivery"), prices.Details)
	group.GET("/delivery/details-mobile", auth, perm("details delivery"), prices.DetailsForMobile)
	group.PUT("/delivery/:id", auth, perm("update delivery"), prices.Update)
	group.DELETE("/delivery/:id", auth, perm("delete delivery"), prices.Delete)

	// Pickup points (no dedicated permission in Laravel).
	group.GET("/pickup-point", pickup.List)
	group.POST("/pickup-point", auth, pickup.Add)
	group.GET("/pickup-point/details", auth, pickup.Details)
	group.GET("/pickup-point/:id", auth, pickup.DetailsAdmin)
	group.PUT("/pickup-point/:id", auth, pickup.Update)
	group.DELETE("/pickup-point/:id", auth, pickup.Delete)

	// City CRUD (admin).
	group.GET("/delivery-city", city.Options)
	group.POST("/delivery-city", auth, city.Add)
	group.GET("/delivery-city/details/:id", auth, city.Details)
	group.PUT("/delivery-city/:id", auth, city.Update)
	group.DELETE("/delivery-city/:id", auth, city.Delete)

	// Delivery info.
	group.GET("/delivery-info", info.List)
	group.GET("/delivery-info/:id", auth, info.ByType)
	group.PUT("/delivery-info/:id", auth, info.Update)
}
