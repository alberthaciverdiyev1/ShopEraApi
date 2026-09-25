package handlers

import (
	"net/http"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	producthelpers "shopera/internal/modules/product/helpers"
	settingservices "shopera/internal/modules/setting/services"
)

// StatisticHandler serves the global statistics endpoint.
type StatisticHandler struct {
	service *settingservices.StatisticService
}

func NewStatisticHandler(service *settingservices.StatisticService) *StatisticHandler {
	return &StatisticHandler{service: service}
}

// Statistics handles GET /api/global-statistics.
func (h *StatisticHandler) Statistics(c *gin.Context) {
	lang := producthelpers.ResolveLocale(c.GetHeader("Accept-Language"))

	data, err := h.service.Statistics(lang)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Statistics retrieved successfully.", data)
}
