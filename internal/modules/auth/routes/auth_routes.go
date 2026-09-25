// Package routes mounts the Auth module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	authhandlers "shopera/internal/modules/auth/handlers"
)

// mount registers the auth routes on the given group.
func mount(group *gin.RouterGroup, handler *authhandlers.AuthHandler, recovery *authhandlers.AuthRecoveryHandler, auth gin.HandlerFunc) {
	authGroup := group.Group("/auth")
	authGroup.POST("/register", handler.Register)
	authGroup.POST("/login", handler.Login)
	authGroup.POST("/refresh", handler.Refresh)
	authGroup.POST("/logout", auth, handler.Logout)

	// OTP and password recovery (phone + e-mail).
	authGroup.POST("/send-otp", recovery.SendOtp)
	authGroup.POST("/check-otp", recovery.CheckOtp)
	authGroup.POST("/reset-password", recovery.ResetPassword)
	authGroup.POST("/password/email-code", recovery.SendPasswordResetEmail)
	authGroup.POST("/password/email-reset", recovery.ResetPasswordByEmail)

	// Password change (authenticated) and admin-mediated reset requests.
	authGroup.POST("/change-password", auth, recovery.ChangePassword)
	authGroup.POST("/admin-change-password", auth, recovery.AdminChangePassword)
	authGroup.POST("/password-reset-requests", recovery.CreateResetRequest)
	authGroup.GET("/password-reset-requests", auth, recovery.ListResetRequests)
	authGroup.PUT("/password-reset-requests/:id/resolve", auth, recovery.ResolveResetRequest)
	authGroup.PUT("/password-reset-requests/:id/dismiss", auth, recovery.DismissResetRequest)
}
