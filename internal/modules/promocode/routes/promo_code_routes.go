// Package routes mounts the PromoCode module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	promocodehandlers "shopera/internal/modules/promocode/handlers"
)

// Register mounts the promo-code routes (all require auth) on the given group.
func mount(group *gin.RouterGroup, handler *promocodehandlers.PromoCodeHandler, auth gin.HandlerFunc) {
	group.GET("/promo-code", auth, handler.List)
	group.GET("/promo-code/check/:code", auth, handler.Check)
	group.GET("/promo-code/calculate-basket-discount-price/:code", auth, handler.CheckWithPrice)
	group.POST("/promo-code", auth, handler.Add)
	group.GET("/promo-code/:id", auth, handler.Details)
	group.PUT("/promo-code/:id", auth, handler.Update)
	group.DELETE("/promo-code/:id", auth, handler.Delete)
}
