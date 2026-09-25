// Package routes mounts the Product module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	producthandlers "shopera/internal/modules/product/handlers"
)

// Register mounts the product routes on the given group.
func Register(group *gin.RouterGroup, handler *producthandlers.ProductHandler, auth gin.HandlerFunc) {
	productGroup := group.Group("/product")
	productGroup.GET("", handler.List)
	productGroup.GET("/:id", handler.Details)
	productGroup.POST("/add", auth, handler.Add)
	productGroup.PUT("/update-prices", auth, handler.UpdatePrices)
	productGroup.PUT("/:id", auth, handler.Update)
	productGroup.DELETE("/:id", auth, handler.Delete)
}
