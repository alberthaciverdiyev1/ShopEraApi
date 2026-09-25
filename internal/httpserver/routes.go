package httpserver

import (
	"github.com/gin-gonic/gin"
	"gorm.io/gorm"

	"shopera/internal/config"
	"shopera/internal/middleware"
	"shopera/internal/modules/auth"
	"shopera/internal/modules/product"
	"shopera/internal/modules/user"
)

// registerModules wires and mounts every feature module under /api.
func registerModules(api *gin.RouterGroup, cfg *config.Config, db *gorm.DB) {
	authMiddleware := middleware.AuthRequired(cfg.JWT.Secret)

	authHandler := auth.NewHandler(auth.NewService(user.NewRepository(db), cfg))
	auth.Register(api, authHandler, authMiddleware)

	productHandler := product.NewHandler(product.NewService(product.NewRepository(db)))
	product.Register(api, productHandler, authMiddleware)
}
