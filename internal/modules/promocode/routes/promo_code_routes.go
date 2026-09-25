// Package routes mounts the PromoCode module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	promocodehandlers "shopera/internal/modules/promocode/handlers"
)

// mount registers the promo-code routes (all require auth) on the given group.
func mount(group *gin.RouterGroup, handler *promocodehandlers.PromoCodeHandler, auth gin.HandlerFunc, perm func(string) gin.HandlerFunc) {
	group.GET("/promo-code", auth, perm("view promo-codes"), handler.List)
	group.GET("/promo-code/check/:code", auth, perm("check promo-code"), handler.Check)
	group.GET("/promo-code/calculate-basket-discount-price/:code", auth, perm("check-promo-code-with-price"), handler.CheckWithPrice)
	group.POST("/promo-code", auth, perm("add promo-code"), handler.Add)
	group.GET("/promo-code/:id", auth, perm("details promo-code"), handler.Details)
	group.PUT("/promo-code/:id", auth, perm("update promo-code"), handler.Update)
	group.DELETE("/promo-code/:id", auth, perm("delete promo-code"), handler.Delete)
}
