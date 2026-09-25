// Package routes mounts the Order module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	orderhandlers "shopera/internal/modules/order/handlers"
)

// Register mounts the order routes (all require auth) on the given group.
func Register(group *gin.RouterGroup, handler *orderhandlers.OrderHandler, auth gin.HandlerFunc) {
	// Static routes first (so they are not shadowed by /:id).
	group.GET("/order/list-admin", auth, handler.AdminList)
	group.GET("/order/preview", auth, handler.Preview)
	group.GET("/order/completed", auth, handler.Completed)
	group.GET("/order/calculate-delivery-price", auth, handler.CalculateDeliveryPrice)

	group.GET("/order", auth, handler.List)
	group.POST("/order", auth, handler.Create)
	group.POST("/order/:product_id", auth, handler.BuyOne)
	group.GET("/order/admin/:id", auth, handler.DetailsAdmin)
	group.GET("/order/:id", auth, handler.Details)
	group.PUT("/order/:id", auth, handler.Update)
	group.DELETE("/order/:id", auth, handler.Delete)
}
