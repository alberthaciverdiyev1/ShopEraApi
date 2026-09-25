// Package httpserver builds the Gin engine and mounts the modules.
package httpserver

import (
	"github.com/gin-gonic/gin"
	"gorm.io/gorm"

	"shopera/internal/config"
	healthroutes "shopera/internal/modules/health/routes"
)

// New returns the configured Gin engine.
func New(cfg *config.Config, db *gorm.DB) *gin.Engine {
	r := gin.Default()
	healthroutes.Register(r)
	registerModules(r.Group("/api"), cfg, db)
	return r
}
