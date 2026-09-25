package httpserver

import (
	"github.com/gin-gonic/gin"
	"gorm.io/gorm"

	"shopera/internal/config"
	"shopera/internal/middleware"
	authhandlers "shopera/internal/modules/auth/handlers"
	authroutes "shopera/internal/modules/auth/routes"
	authservices "shopera/internal/modules/auth/services"
	producthandlers "shopera/internal/modules/product/handlers"
	productrepositories "shopera/internal/modules/product/repositories"
	productroutes "shopera/internal/modules/product/routes"
	productservices "shopera/internal/modules/product/services"
	userrepositories "shopera/internal/modules/user/repositories"
)

// registerModules wires and mounts every feature module under /api.
func registerModules(api *gin.RouterGroup, cfg *config.Config, db *gorm.DB) {
	authMiddleware := middleware.AuthRequired(cfg.JWT.Secret)

	authHandler := authhandlers.NewAuthHandler(
		authservices.NewAuthService(userrepositories.NewUserRepository(db), cfg),
	)
	authroutes.Register(api, authHandler, authMiddleware)

	productHandler := producthandlers.NewProductHandler(
		productservices.NewProductService(productrepositories.NewProductRepository(db)),
	)
	productroutes.Register(api, productHandler, authMiddleware)
}
