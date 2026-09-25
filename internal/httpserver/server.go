// Package httpserver builds the Gin engine and mounts the modules.
package httpserver

import (
	"github.com/gin-gonic/gin"
	"gorm.io/gorm"

	"shopera/internal/config"
	"shopera/internal/middleware"
	healthroutes "shopera/internal/modules/health/routes"
)

// New returns the configured Gin engine.
func New(cfg *config.Config, db *gorm.DB) *gin.Engine {
	r := gin.Default()
	r.Use(middleware.CORS(cfg.App.CORSOrigins))

	// Serve uploaded files (Laravel Storage::disk('public')->url parity):
	// /storage/categories/<file> -> ./storage/app/public/categories/<file>.
	r.Static("/storage", "./storage/app/public")

	healthroutes.Register(r)
	registerModules(r.Group("/api"), cfg, db)
	return r
}
