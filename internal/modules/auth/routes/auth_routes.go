// Package routes mounts the Auth module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	authhandlers "shopera/internal/modules/auth/handlers"
)

// Register mounts the auth routes on the given group.
func Register(group *gin.RouterGroup, handler *authhandlers.AuthHandler, auth gin.HandlerFunc) {
	authGroup := group.Group("/auth")
	authGroup.POST("/register", handler.Register)
	authGroup.POST("/login", handler.Login)
	authGroup.POST("/logout", auth, handler.Logout)
}
