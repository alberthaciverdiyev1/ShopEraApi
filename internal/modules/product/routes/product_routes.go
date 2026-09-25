// Package routes mounts the Product module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	producthandlers "shopera/internal/modules/product/handlers"
)

// mount registers the product routes on the given group.
func mount(group *gin.RouterGroup, handler *producthandlers.ProductHandler, auth gin.HandlerFunc, perm func(string) gin.HandlerFunc) {
	productGroup := group.Group("/product")
	productGroup.GET("", handler.List)
	productGroup.GET("/details/:id", auth, perm("details-admin product"), handler.DetailsAdmin)
	productGroup.GET("/statistics", handler.Statistics)
	productGroup.GET("/recommend", handler.Recommended)
	productGroup.GET("/story-videos", handler.StoryVideos)
	productGroup.GET("/story-videos/admin", auth, handler.StoryVideosAdmin)
	productGroup.POST("/story-videos/:id/activate", auth, handler.ActivateStoryVideo)
	productGroup.POST("/story-videos/:id/deactivate", auth, handler.DeactivateStoryVideo)
	productGroup.GET("/:id", handler.Details)
	productGroup.POST("/add", auth, perm("add product"), handler.Add)
	productGroup.PUT("/update-prices", auth, perm("update product"), handler.UpdatePrices)
	productGroup.POST("/subscribe", auth, handler.Subscribe)
	productGroup.POST("/unsubscribe", auth, handler.Unsubscribe)
	productGroup.PUT("/:id", auth, perm("update product"), handler.Update)
	productGroup.DELETE("/:id", auth, perm("delete product"), handler.Delete)
}
