// Package handlers holds Basket module HTTP handlers.
package handlers

import (
	"net/http"

	"github.com/gin-gonic/gin"
	"gorm.io/gorm"

	"shopera/internal/helpers"
	basketrepositories "shopera/internal/modules/basket/repositories"
	basketrequests "shopera/internal/modules/basket/requests"
	basketservices "shopera/internal/modules/basket/services"
	producthelpers "shopera/internal/modules/product/helpers"
	productrepositories "shopera/internal/modules/product/repositories"
)

// BasketHandler serves the basket endpoints.
type BasketHandler struct {
	service *basketservices.BasketService
}

func NewBasketHandler(db *gorm.DB) *BasketHandler {
	return &BasketHandler{
		service: basketservices.NewBasketService(
			basketrepositories.NewBasketRepository(db),
			productrepositories.NewProductRepository(db),
		),
	}
}

// List handles GET /api/basket.
func (h *BasketHandler) List(c *gin.Context) {
	lang := producthelpers.ResolveLocale(c.GetHeader("Accept-Language"))
	result, err := h.service.List(c.GetInt64("userID"), helpers.ParseQuery(c), lang)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Basket retrieved successfully.", result)
}

// Add handles POST /api/basket.
func (h *BasketHandler) Add(c *gin.Context) {
	var req basketrequests.AddRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	if err := h.service.Add(c.GetInt64("userID"), req); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Basket added successfully.", nil)
}

// Update handles PUT /api/basket/:id.
func (h *BasketHandler) Update(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusForbidden, "Basket not found.", nil)
		return
	}
	var req basketrequests.UpdateRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	if err := h.service.Update(c.GetInt64("userID"), id, req); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Basket updated successfully.", nil)
}

// Delete handles DELETE /api/basket/:id.
func (h *BasketHandler) Delete(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusForbidden, "Basket not found.", nil)
		return
	}
	if err := h.service.Delete(c.GetInt64("userID"), id); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Basket deleted successfully.", nil)
}
