package handlers

import (
	"net/http"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	helprequests "shopera/internal/modules/helpandpolicy/requests"
	helpresponses "shopera/internal/modules/helpandpolicy/responses"
	helpservices "shopera/internal/modules/helpandpolicy/services"
)

// LegalTermHandler serves the legal-terms endpoints.
type LegalTermHandler struct {
	service *helpservices.LegalTermService
}

func NewLegalTermHandler(service *helpservices.LegalTermService) *LegalTermHandler {
	return &LegalTermHandler{service: service}
}

// List handles GET /api/legal-terms.
func (h *LegalTermHandler) List(c *gin.Context) {
	lang := helpers.ResolveLocale(c.GetHeader("Accept-Language"))
	items, err := h.service.List(optionalQuery(c, "type"))
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Legal Terms retrieved successfully.", helpresponses.LegalTermCollection(items, lang))
}

// ListAdmin handles GET /api/legal-terms/admin.
func (h *LegalTermHandler) ListAdmin(c *gin.Context) {
	lang := helpers.ResolveLocale(c.GetHeader("Accept-Language"))
	items, err := h.service.List(optionalQuery(c, "type"))
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Legal Terms retrieved successfully.", helpresponses.LegalTermCollection(items, lang))
}

// PrivacyAndPolicy handles GET /api/privacy-and-policy (raw HTML).
func (h *LegalTermHandler) PrivacyAndPolicy(c *gin.Context) {
	html, err := h.service.PrivacyAndPolicy()
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	c.Data(http.StatusOK, "text/html; charset=UTF-8", []byte(html))
}

// Update handles PUT /api/legal-terms/:type.
func (h *LegalTermHandler) Update(c *gin.Context) {
	lang := helpers.ResolveLocale(c.GetHeader("Accept-Language"))
	termType := c.Param("type")
	var req helprequests.LegalTermUpdateRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	term, err := h.service.Update(termType, req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Legal Terms updated successfully.", helpresponses.LegalTermJSON(*term, lang))
}
