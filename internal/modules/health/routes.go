package health

import "github.com/gin-gonic/gin"

// Register mounts the health route on the engine.
func Register(engine *gin.Engine) {
	engine.GET("/health", Show)
}
