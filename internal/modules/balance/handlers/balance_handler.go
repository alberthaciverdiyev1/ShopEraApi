// Package handlers holds Balance module HTTP handlers.
package handlers

import (
	"net/http"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	balancerequests "shopera/internal/modules/balance/requests"
	balanceresponses "shopera/internal/modules/balance/responses"
	balanceservices "shopera/internal/modules/balance/services"
)

// BalanceHandler serves the balance endpoints.
type BalanceHandler struct {
	service *balanceservices.BalanceService
}

func NewBalanceHandler(service *balanceservices.BalanceService) *BalanceHandler {
	return &BalanceHandler{service: service}
}

// Deposit handles POST /api/balance/deposit.
func (h *BalanceHandler) Deposit(c *gin.Context) {
	var req balancerequests.DepositRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	balance, err := h.service.Deposit(c.GetInt64("userID"), req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Balance deposited successfully.", balanceresponses.JSON(*balance))
}

// History handles GET /api/balance/history.
func (h *BalanceHandler) History(c *gin.Context) {
	userID := c.GetInt64("userID")
	if v := c.Query("user_id"); v != "" {
		if n, ok := helpers.ParseInt(v); ok {
			userID = n
		}
	}
	isAdmin := c.Query("is_admin") == "1" || c.Query("is_admin") == "true"
	items, err := h.service.History(userID, isAdmin)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Balance history retrieved successfully.", items)
}

// Increase handles POST /api/balance/increase.
func (h *BalanceHandler) Increase(c *gin.Context) {
	var req balancerequests.IncreaseRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	result, err := h.service.Increase(c.Request.Context(), c.GetInt64("userID"), req.Amount)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Payment initialized successfully.", result)
}

// Success handles GET /api/balance/success.
func (h *BalanceHandler) Success(c *gin.Context) {
	result, err := h.service.Success(c.Query("transaction_id"))
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Balance deposited successfully.", result)
}

// Error handles GET /api/balance/error.
func (h *BalanceHandler) Error(c *gin.Context) {
	result, err := h.service.Error(c.Query("transaction_id"))
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusBadRequest, "Payment failed, balance record deleted.", result)
}
