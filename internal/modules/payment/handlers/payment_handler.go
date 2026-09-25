// Package handlers holds Payment module HTTP handlers.
package handlers

import (
	"net/http"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	paymentrepositories "shopera/internal/modules/payment/repositories"
	paymentrequests "shopera/internal/modules/payment/requests"
	paymentresponses "shopera/internal/modules/payment/responses"
	paymentservices "shopera/internal/modules/payment/services"
)

// PaymentHandler serves the payment endpoints.
type PaymentHandler struct {
	service *paymentservices.PaymentService
}

func NewPaymentHandler(service *paymentservices.PaymentService) *PaymentHandler {
	return &PaymentHandler{service: service}
}

// Providers handles GET /api/payment/providers (active, public).
func (h *PaymentHandler) Providers(c *gin.Context) {
	items, err := h.service.ListProviders(false)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	out := make([]gin.H, 0, len(items))
	for _, p := range items {
		out = append(out, paymentresponses.ProviderPublicJSON(p))
	}
	helpers.Respond(c, http.StatusOK, "Payment providers retrieved successfully.", out)
}

// ProvidersAdmin handles GET /api/payment/providers/admin.
func (h *PaymentHandler) ProvidersAdmin(c *gin.Context) {
	items, err := h.service.ListProviders(true)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	out := make([]gin.H, 0, len(items))
	for _, p := range items {
		out = append(out, paymentresponses.ProviderAdminJSON(p))
	}
	helpers.Respond(c, http.StatusOK, "Payment providers retrieved successfully.", out)
}

// SaveProvider handles POST /api/payment/providers (admin selects + fills data).
func (h *PaymentHandler) SaveProvider(c *gin.Context) {
	var req paymentrequests.SaveProviderRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	provider, err := h.service.SaveProvider(req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Payment provider saved successfully.", paymentresponses.ProviderAdminJSON(*provider))
}

// Create handles POST /api/payment/create.
func (h *PaymentHandler) Create(c *gin.Context) {
	var req paymentrequests.CreatePaymentRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	redirectURL, err := h.service.CreatePayment(c.Request.Context(), req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Payment started successfully.", gin.H{"redirect_url": redirectURL})
}

// Result handles GET|POST /api/payment/result (provider callback).
func (h *PaymentHandler) Result(c *gin.Context) {
	result, err := h.service.HandleCallback(callbackParams(c))
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Payment result received.", gin.H{
		"provider":       result.Provider,
		"order_id":       result.OrderID,
		"transaction_id": result.TransactionID,
		"paid":           result.Paid,
	})
}

// Success handles GET /api/payment/success.
func (h *PaymentHandler) Success(c *gin.Context) {
	helpers.Respond(c, http.StatusOK, "Payment completed successfully.", gin.H{"transaction_id": c.Query("transaction_id")})
}

// Error handles GET /api/payment/error.
func (h *PaymentHandler) Error(c *gin.Context) {
	helpers.Respond(c, http.StatusOK, "Payment failed.", gin.H{"transaction_id": c.Query("transaction_id")})
}

// Status handles GET /api/payment/status.
func (h *PaymentHandler) Status(c *gin.Context) {
	helpers.Respond(c, http.StatusOK, "Payment status retrieved.", gin.H{"transaction_id": c.Query("transaction_id")})
}

func callbackParams(c *gin.Context) map[string]string {
	params := map[string]string{}
	for k, v := range c.Request.URL.Query() {
		if len(v) > 0 {
			params[k] = v[0]
		}
	}
	_ = c.Request.ParseForm()
	for k, v := range c.Request.PostForm {
		if len(v) > 0 {
			params[k] = v[0]
		}
	}
	return params
}

var _ = paymentrepositories.NewPaymentProviderRepository
