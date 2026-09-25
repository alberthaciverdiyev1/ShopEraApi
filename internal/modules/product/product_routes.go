package product

import "github.com/gin-gonic/gin"

// Register mounts the product routes on the given group.
func Register(group *gin.RouterGroup, handler *ProductHandler, auth gin.HandlerFunc) {
	productGroup := group.Group("/product")
	productGroup.GET("", handler.List)
	productGroup.GET("/:id", handler.Details)
	productGroup.POST("/add", auth, handler.Create)
}
