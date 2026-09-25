// Package routes mounts the Balance module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	balancehandlers "shopera/internal/modules/balance/handlers"
)

// Register mounts the balance routes on the given group.
func Register(group *gin.RouterGroup, handler *balancehandlers.BalanceHandler, auth gin.HandlerFunc) {
	group.POST("/balance/deposit", auth, handler.Deposit)
	group.GET("/balance/history", auth, handler.History)
	group.POST("/balance/increase", auth, handler.Increase)
	group.GET("/balance/success", handler.Success)
	group.GET("/balance/error", handler.Error)
}
