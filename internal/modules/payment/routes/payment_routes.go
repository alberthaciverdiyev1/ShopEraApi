// Package routes mounts the Payment module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	paymenthandlers "shopera/internal/modules/payment/handlers"
)

// Register mounts the payment routes on the given group.
func mount(group *gin.RouterGroup, handler *paymenthandlers.PaymentHandler, auth gin.HandlerFunc) {
	group.GET("/payment/providers", handler.Providers)
	group.GET("/payment/providers/admin", auth, handler.ProvidersAdmin)
	group.POST("/payment/providers", auth, handler.SaveProvider)
	group.POST("/payment/create", handler.Create)
	group.GET("/payment/result", handler.Result)
	group.POST("/payment/result", handler.Result)
	group.GET("/payment/success", handler.Success)
	group.GET("/payment/error", handler.Error)
	group.GET("/payment/status", handler.Status)
}
