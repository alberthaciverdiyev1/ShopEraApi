// Package handlers holds PromoCode module HTTP handlers.
package handlers

import (
	"net/http"
	"strconv"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	promocoderequests "shopera/internal/modules/promocode/requests"
	promocoderesponses "shopera/internal/modules/promocode/responses"
	promocodeservices "shopera/internal/modules/promocode/services"
)

// PromoCodeHandler serves the promo-code endpoints.
type PromoCodeHandler struct {
	service *promocodeservices.PromoCodeService
}

func NewPromoCodeHandler(service *promocodeservices.PromoCodeService) *PromoCodeHandler {
	return &PromoCodeHandler{service: service}
}

// List handles GET /api/promo-code.
func (h *PromoCodeHandler) List(c *gin.Context) {
	items, err := h.service.List(helpers.ParseQuery(c), helpers.QueryBoolPtr(c, "is_active"))
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Promo Codes retrieved successfully.", items)
}

// Details handles GET /api/promo-code/:id.
func (h *PromoCodeHandler) Details(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusForbidden, "Promo Code not found.", []any{})
		return
	}
	promo, err := h.service.Details(id)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	if promo == nil {
		helpers.Respond(c, http.StatusForbidden, "Promo Code not found.", []any{})
		return
	}
	helpers.Respond(c, http.StatusOK, "Promo Codes details retrieved successfully.", promocoderesponses.JSON(*promo))
}

// Check handles GET /api/promo-code/check/:code.
func (h *PromoCodeHandler) Check(c *gin.Context) {
	result, err := h.service.Check(c.GetInt64("userID"), c.Param("code"), addressID(c))
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Promo Code checked successfully.", result)
}

// CheckWithPrice handles GET /api/promo-code/calculate-basket-discount-price/:code.
func (h *PromoCodeHandler) CheckWithPrice(c *gin.Context) {
	result, err := h.service.Check(c.GetInt64("userID"), c.Param("code"), addressID(c))
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Promo code checked successfully.", gin.H{
		"original_price":   result["original_price"],
		"discounted_price": result["discounted_price"],
	})
}

// Add handles POST /api/promo-code.
func (h *PromoCodeHandler) Add(c *gin.Context) {
	var req promocoderequests.SaveRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	promo, err := h.service.Add(req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Promo Code added successfully.", promocoderesponses.JSON(*promo))
}

// Update handles PUT /api/promo-code/:id.
func (h *PromoCodeHandler) Update(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusForbidden, "Promo Code not found.", nil)
		return
	}
	var req promocoderequests.SaveRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	promo, err := h.service.Update(id, req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Promo Code updated successfully.", promocoderesponses.JSON(*promo))
}

// Delete handles DELETE /api/promo-code/:id.
func (h *PromoCodeHandler) Delete(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusForbidden, "Promo Code not found.", nil)
		return
	}
	if err := h.service.Delete(id); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Promo Code deleted successfully.", nil)
}

func addressID(c *gin.Context) *int64 {
	if v := c.Query("address_id"); v != "" {
		if n, err := strconv.ParseInt(v, 10, 64); err == nil {
			return &n
		}
	}
	return nil
}
