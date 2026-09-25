package httpserver

import (
	"github.com/gin-gonic/gin"
	"gorm.io/gorm"

	"shopera/internal/config"
	"shopera/internal/middleware"
	"shopera/internal/modules/auth"
	"shopera/internal/modules/product"
	productrepositories "shopera/internal/modules/product/repositories"
	userrepositories "shopera/internal/modules/user/repositories"
)

// registerModules wires and mounts every feature module under /api.
func registerModules(api *gin.RouterGroup, cfg *config.Config, db *gorm.DB) {
	authMiddleware := middleware.AuthRequired(cfg.JWT.Secret)

	authHandler := auth.NewAuthHandler(auth.NewAuthService(userrepositories.NewUserRepository(db), cfg))
	auth.Register(api, authHandler, authMiddleware)

	productHandler := product.NewProductHandler(product.NewProductService(productrepositories.NewProductRepository(db)))
	product.Register(api, productHandler, authMiddleware)
}
