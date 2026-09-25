package auth

import "github.com/gin-gonic/gin"

// Register mounts the auth routes on the given group.
func Register(group *gin.RouterGroup, handler *AuthHandler, auth gin.HandlerFunc) {
	authGroup := group.Group("/auth")
	authGroup.POST("/register", handler.Register)
	authGroup.POST("/login", handler.Login)
	authGroup.POST("/logout", auth, handler.Logout)
}
