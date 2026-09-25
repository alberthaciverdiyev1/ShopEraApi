// Package routes mounts the Balance module routes.
package routes

import (
	"github.com/gin-gonic/gin"

	balancehandlers "shopera/internal/modules/balance/handlers"
)

// mount registers the balance routes on the given group.
func mount(group *gin.RouterGroup, handler *balancehandlers.BalanceHandler, auth gin.HandlerFunc, perm func(string) gin.HandlerFunc) {
	group.POST("/balance/deposit", auth, perm("deposit balance"), handler.Deposit)
	group.GET("/balance/history", auth, perm("view balances"), perm("history balance"), handler.History)
	group.POST("/balance/increase", auth, handler.Increase)
	group.GET("/balance/success", handler.Success)
	group.GET("/balance/error", handler.Error)
}
