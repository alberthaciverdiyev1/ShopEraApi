// Package handlers holds Popup module HTTP handlers.
package handlers

import (
	"net/http"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	popupresponses "shopera/internal/modules/popup/responses"
	popupservices "shopera/internal/modules/popup/services"
)

// PopupHandler serves the popup endpoints.
type PopupHandler struct {
	service *popupservices.PopupService
}

func NewPopupHandler(service *popupservices.PopupService) *PopupHandler {
	return &PopupHandler{service: service}
}

// List handles GET /api/popup.
func (h *PopupHandler) List(c *gin.Context) {
	result, err := h.service.List(helpers.ParseQuery(c))
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Popups retrieved successfully", result)
}

// ShowOne handles GET /api/popup/show-one.
func (h *PopupHandler) ShowOne(c *gin.Context) {
	p, err := h.service.ShowOne()
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	if p == nil {
		helpers.Respond(c, http.StatusNotFound, "Popups not found.", []any{})
		return
	}
	helpers.Respond(c, http.StatusOK, "Popups retrieved successfully", popupresponses.JSON(*p))
}

// Add handles POST /api/popup (multipart: image or video).
func (h *PopupHandler) Add(c *gin.Context) {
	var image, video *string
	if _, err := c.FormFile("image"); err == nil {
		if path, err := helpers.SaveUpload(c, "image", "popups"); err == nil {
			image = &path
		}
	} else if _, err := c.FormFile("video"); err == nil {
		if path, err := helpers.SaveUpload(c, "video", "popups/videos"); err == nil {
			video = &path
		}
	}

	showOnHome := c.PostForm("show_on_home_page") == "1" || c.PostForm("show_on_home_page") == "true"

	if err := h.service.Add(image, video, showOnHome); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusCreated, "Popup added successfully.", nil)
}

// ShowHome handles PUT /api/popup/:id (toggles the home-page popup).
func (h *PopupHandler) ShowHome(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Popup not found.", nil)
		return
	}
	p, err := h.service.ShowHome(id)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Popup updated successfully.", popupresponses.JSON(*p))
}

// Delete handles DELETE /api/popup/:id.
func (h *PopupHandler) Delete(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Popup not found.", nil)
		return
	}
	if err := h.service.Delete(id); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Popup deleted successfully.", nil)
}
