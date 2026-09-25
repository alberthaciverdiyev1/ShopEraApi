// Package handlers holds HelpAndPolicy module HTTP handlers.
package handlers

import (
	"net/http"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	helprequests "shopera/internal/modules/helpandpolicy/requests"
	helpresponses "shopera/internal/modules/helpandpolicy/responses"
	helpservices "shopera/internal/modules/helpandpolicy/services"
)

// FaqHandler serves the faq endpoints.
type FaqHandler struct {
	service *helpservices.FaqService
}

func NewFaqHandler(service *helpservices.FaqService) *FaqHandler {
	return &FaqHandler{service: service}
}

// List handles GET /api/faq.
func (h *FaqHandler) List(c *gin.Context) {
	lang := helpers.ResolveLocale(c.GetHeader("Accept-Language"))
	items, err := h.service.List(optionalQuery(c, "type"))
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Faqs retrieved successfully.", helpresponses.FaqCollection(items, lang))
}

// ListAdmin handles GET /api/faq/admin.
func (h *FaqHandler) ListAdmin(c *gin.Context) {
	lang := helpers.ResolveLocale(c.GetHeader("Accept-Language"))
	items, err := h.service.List(optionalQuery(c, "type"))
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Faqs retrieved successfully.", helpresponses.FaqCollection(items, lang))
}

// Add handles POST /api/faq.
func (h *FaqHandler) Add(c *gin.Context) {
	lang := helpers.ResolveLocale(c.GetHeader("Accept-Language"))
	var req helprequests.FaqSaveRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	faq, err := h.service.Add(req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Faq added successfully.", helpresponses.FaqJSON(*faq, lang))
}

// Update handles PUT /api/faq/:id.
func (h *FaqHandler) Update(c *gin.Context) {
	lang := helpers.ResolveLocale(c.GetHeader("Accept-Language"))
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Faq not found.", nil)
		return
	}
	var req helprequests.FaqSaveRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	faq, err := h.service.Update(id, req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Faq updated successfully.", helpresponses.FaqJSON(*faq, lang))
}

// Delete handles DELETE /api/faq/:id.
func (h *FaqHandler) Delete(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Faq not found.", nil)
		return
	}
	if err := h.service.Delete(id); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Faq deleted successfully.", nil)
}

func optionalQuery(c *gin.Context, key string) *string {
	if v, ok := c.GetQuery(key); ok && v != "" {
		return &v
	}
	return nil
}
