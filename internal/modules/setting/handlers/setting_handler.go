// Package handlers holds Setting module HTTP handlers.
package handlers

import (
	"net/http"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	settingmodels "shopera/internal/modules/setting/models"
	settingrequests "shopera/internal/modules/setting/requests"
	settingservices "shopera/internal/modules/setting/services"
)

// SettingHandler serves the setting endpoints.
type SettingHandler struct {
	service *settingservices.SettingService
}

func NewSettingHandler(service *settingservices.SettingService) *SettingHandler {
	return &SettingHandler{service: service}
}

// List handles GET /api/setting.
func (h *SettingHandler) List(c *gin.Context) {
	items, err := h.service.List()
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	if items == nil {
		items = []settingmodels.Setting{}
	}
	helpers.Respond(c, http.StatusOK, "Settings retrieved successfully.", items)
}

// Update handles PUT /api/setting.
func (h *SettingHandler) Update(c *gin.Context) {
	var req settingrequests.UpdateRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	setting, err := h.service.Update(req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Setting updated successfully.", setting)
}

// ChangeLocale handles POST /api/change-locale.
func (h *SettingHandler) ChangeLocale(c *gin.Context) {
	var req settingrequests.ChangeLocaleRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	locale, err := h.service.ChangeLocale(req.Locale)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Locale changed successfully.", gin.H{"locale": locale})
}
