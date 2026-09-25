// Package handlers holds Banner module HTTP handlers.
package handlers

import (
	"net/http"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	bannerservices "shopera/internal/modules/banner/services"
)

// BannerHandler serves the banner endpoints.
type BannerHandler struct {
	service *bannerservices.BannerService
}

func NewBannerHandler(service *bannerservices.BannerService) *BannerHandler {
	return &BannerHandler{service: service}
}

// List handles GET /api/banner.
func (h *BannerHandler) List(c *gin.Context) {
	var bannerType *string
	if v, ok := c.GetQuery("type"); ok && v != "" {
		bannerType = &v
	}
	result, err := h.service.List(helpers.ParseQuery(c), bannerType)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Banners retrieved successfully", result)
}

// Add handles POST /api/banner (multipart).
func (h *BannerHandler) Add(c *gin.Context) {
	if _, err := c.FormFile("image"); err != nil {
		helpers.Respond(c, http.StatusUnprocessableEntity, "Image is required", nil)
		return
	}
	bannerType := c.PostForm("type")
	if bannerType == "" {
		helpers.Respond(c, http.StatusUnprocessableEntity, "Type is required", nil)
		return
	}

	image, err := helpers.SaveUpload(c, "image", "banner")
	if err != nil {
		helpers.Respond(c, http.StatusUnprocessableEntity, "Invalid image file", nil)
		return
	}

	var second *string
	if _, err := c.FormFile("second_image"); err == nil {
		if path, err := helpers.SaveUpload(c, "second_image", "banner"); err == nil {
			second = &path
		}
	}

	var url *string
	if v, ok := c.GetPostForm("url"); ok && v != "" {
		url = &v
	}

	if err := h.service.Add(image, second, bannerType, url); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Banner added successfully", nil)
}

// Delete handles DELETE /api/banner/:id.
func (h *BannerHandler) Delete(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Banner not found", nil)
		return
	}
	if err := h.service.Delete(id); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Banner deleted successfully", nil)
}
