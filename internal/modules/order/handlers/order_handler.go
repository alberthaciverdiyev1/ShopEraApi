// Package handlers holds Order module HTTP handlers.
package handlers

import (
	"net/http"
	"strconv"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	orderrequests "shopera/internal/modules/order/requests"
	orderservices "shopera/internal/modules/order/services"
	producthelpers "shopera/internal/modules/product/helpers"
)

// OrderHandler serves the order endpoints.
type OrderHandler struct {
	service *orderservices.OrderService
}

func NewOrderHandler(service *orderservices.OrderService) *OrderHandler {
	return &OrderHandler{service: service}
}

// List handles GET /api/order.
func (h *OrderHandler) List(c *gin.Context) {
	lang := producthelpers.ResolveLocale(c.GetHeader("Accept-Language"))
	result, err := h.service.List(c.GetInt64("userID"), helpers.ParseQuery(c), lang)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Orders retrieved successfully.", result)
}

// AdminList handles GET /api/order/list-admin.
func (h *OrderHandler) AdminList(c *gin.Context) {
	lang := producthelpers.ResolveLocale(c.GetHeader("Accept-Language"))
	result, err := h.service.AdminList(helpers.ParseQuery(c), lang)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Orders retrieved successfully.", result)
}

// Completed handles GET /api/order/completed.
func (h *OrderHandler) Completed(c *gin.Context) {
	lang := producthelpers.ResolveLocale(c.GetHeader("Accept-Language"))
	result, err := h.service.Completed(c.GetInt64("userID"), helpers.ParseQuery(c), lang)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Orders retrieved successfully.", result)
}

// Preview handles GET /api/order/preview.
func (h *OrderHandler) Preview(c *gin.Context) {
	lang := producthelpers.ResolveLocale(c.GetHeader("Accept-Language"))
	result, err := h.service.PreviewOrder(c.GetInt64("userID"), c.Query("address_type"), queryID(c), lang)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Order preview retrieved successfully.", result)
}

// Create handles POST /api/order (order from basket).
func (h *OrderHandler) Create(c *gin.Context) {
	var req orderrequests.CreateRequest
	if err := c.ShouldBind(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	lang := producthelpers.ResolveLocale(c.GetHeader("Accept-Language"))
	result, err := h.service.OrderFromBasket(c.GetInt64("userID"), req, lang)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Order created successfully.", result)
}

// BuyOne handles POST /api/order/:product_id.
func (h *OrderHandler) BuyOne(c *gin.Context) {
	productID, err := strconv.ParseInt(c.Param("product_id"), 10, 64)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Product not found.", nil)
		return
	}
	var req orderrequests.BuyOneRequest
	if err := c.ShouldBind(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	lang := producthelpers.ResolveLocale(c.GetHeader("Accept-Language"))
	result, err := h.service.BuyOne(c.GetInt64("userID"), productID, req, lang)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Order created successfully.", result)
}

// Details handles GET /api/order/:id.
func (h *OrderHandler) Details(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Order not found.", nil)
		return
	}
	lang := producthelpers.ResolveLocale(c.GetHeader("Accept-Language"))
	result, err := h.service.Details(c.GetInt64("userID"), id, lang)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	if result == nil {
		helpers.Respond(c, http.StatusNotFound, "Order not found.", nil)
		return
	}
	helpers.Respond(c, http.StatusOK, "Order details retrieved successfully.", result)
}

// DetailsAdmin handles GET /api/order/admin/:id.
func (h *OrderHandler) DetailsAdmin(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Order not found.", nil)
		return
	}
	lang := producthelpers.ResolveLocale(c.GetHeader("Accept-Language"))
	result, err := h.service.DetailsAdmin(id, lang)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	if result == nil {
		helpers.Respond(c, http.StatusNotFound, "Order not found.", nil)
		return
	}
	helpers.Respond(c, http.StatusOK, "Order details retrieved successfully.", result)
}

// Update handles PUT /api/order/:id.
func (h *OrderHandler) Update(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Order not found.", nil)
		return
	}
	var req orderrequests.UpdateRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	lang := producthelpers.ResolveLocale(c.GetHeader("Accept-Language"))
	result, err := h.service.Update(id, req, lang)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Order updated successfully.", result)
}

// Delete handles DELETE /api/order/:id.
func (h *OrderHandler) Delete(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Order not found.", nil)
		return
	}
	if err := h.service.Delete(id); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Order deleted successfully.", nil)
}

// CalculateDeliveryPrice handles GET /api/order/calculate-delivery-price.
func (h *OrderHandler) CalculateDeliveryPrice(c *gin.Context) {
	price, err := h.service.CalculateDeliveryPrice(c.GetInt64("userID"), c.Query("address_type"), queryID(c))
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Delivery price calculated successfully.", gin.H{"shipping_price": price})
}

func queryID(c *gin.Context) *int64 {
	if v := c.Query("address_type_id"); v != "" {
		if n, err := strconv.ParseInt(v, 10, 64); err == nil {
			return &n
		}
	}
	return nil
}
