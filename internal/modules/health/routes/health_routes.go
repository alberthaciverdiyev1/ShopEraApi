// Package routes mounts the Health module route.
package routes

import (
	"github.com/gin-gonic/gin"

	healthhandlers "shopera/internal/modules/health/handlers"
)

// Register mounts the health route on the engine.
func Register(engine *gin.Engine) {
	engine.GET("/health", healthhandlers.Show)
}
