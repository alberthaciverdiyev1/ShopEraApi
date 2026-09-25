// Package routes mounts the Order module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	orderhandlers "shopera/internal/modules/order/handlers"
)

// mount registers the order routes (all require auth) on the given group.
func mount(group *gin.RouterGroup, handler *orderhandlers.OrderHandler, auth gin.HandlerFunc, perm func(string) gin.HandlerFunc) {
	// Static routes first (so they are not shadowed by /:id).
	group.GET("/order/list-admin", auth, perm("view orders-admin"), handler.AdminList)
	group.GET("/order/preview", auth, handler.Preview)
	group.GET("/order/completed", auth, perm("completed-orders"), handler.Completed)
	group.GET("/order/calculate-delivery-price", auth, handler.CalculateDeliveryPrice)
	group.GET("/order/receipt/:order_id", auth, perm("view-receipt"), handler.Receipt)
	group.GET("/order/download-receipt/:order_id", auth, perm("download-receipt"), handler.DownloadReceipt)

	group.GET("/order", auth, perm("view orders"), handler.List)
	group.POST("/order", auth, perm("basket order"), handler.Create)
	group.POST("/order/:product_id", auth, perm("buy-one order"), handler.BuyOne)
	group.GET("/order/admin/:id", auth, handler.DetailsAdmin)
	group.GET("/order/:id", auth, perm("view orders"), handler.Details)
	group.PUT("/order/:id", auth, perm("update order"), handler.Update)
	group.DELETE("/order/:id", auth, perm("delete order"), handler.Delete)
}
