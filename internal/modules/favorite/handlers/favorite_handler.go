// Package handlers holds Favorite module HTTP handlers.
package handlers

import (
	"net/http"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	favoriteservices "shopera/internal/modules/favorite/services"
	producthelpers "shopera/internal/modules/product/helpers"
)

// FavoriteHandler serves the favorite endpoints.
type FavoriteHandler struct {
	service *favoriteservices.FavoriteService
}

func NewFavoriteHandler(service *favoriteservices.FavoriteService) *FavoriteHandler {
	return &FavoriteHandler{service: service}
}

// List handles GET /api/favorite.
func (h *FavoriteHandler) List(c *gin.Context) {
	lang := producthelpers.ResolveLocale(c.GetHeader("Accept-Language"))
	result, err := h.service.List(c.GetInt64("userID"), helpers.ParseQuery(c), lang)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Favorites retrieved successfully.", result)
}

// Add handles POST /api/favorite/:id (toggle).
func (h *FavoriteHandler) Add(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusForbidden, "Product not found.", nil)
		return
	}
	message, err := h.service.Add(c.GetInt64("userID"), id)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, message, nil)
}

// Delete handles DELETE /api/favorite/:id.
func (h *FavoriteHandler) Delete(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusForbidden, "Product not found.", nil)
		return
	}
	message, err := h.service.Delete(c.GetInt64("userID"), id)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, message, nil)
}
