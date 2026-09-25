// Package handlers holds Brand module HTTP handlers.
package handlers

import (
	"errors"
	"net/http"
	"strconv"
	"strings"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	brandrequests "shopera/internal/modules/brand/requests"
	brandresponses "shopera/internal/modules/brand/responses"
	brandservices "shopera/internal/modules/brand/services"
)

// BrandHandler serves the brand endpoints.
type BrandHandler struct {
	service *brandservices.BrandService
}

func NewBrandHandler(service *brandservices.BrandService) *BrandHandler {
	return &BrandHandler{service: service}
}

// List handles GET /api/brand.
func (h *BrandHandler) List(c *gin.Context) {
	result, err := h.service.List(helpers.ParseQuery(c))
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Brands retrieved successfully.", result)
}

// Details handles GET /api/brand/:id.
func (h *BrandHandler) Details(c *gin.Context) {
	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		helpers.Respond(c, http.StatusForbidden, "Brand not found.", nil)
		return
	}

	item, err := h.service.Details(id)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	if item == nil {
		helpers.Respond(c, http.StatusForbidden, "Brand not found.", nil)
		return
	}
	helpers.Respond(c, http.StatusOK, "Brand details retrieved successfully.", brandresponses.JSON(*item))
}

// Add handles POST /api/brand.
func (h *BrandHandler) Add(c *gin.Context) {
	req := bindBrandSave(c)
	if req.Name == "" {
		helpers.ValidationFailed(c, errors.New("name is required"))
		return
	}

	brand, err := h.service.Add(req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Brand added successfully.", brandresponses.JSON(*brand))
}

// Update handles PUT /api/brand/:id.
func (h *BrandHandler) Update(c *gin.Context) {
	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		helpers.Respond(c, http.StatusForbidden, "Brand not found.", nil)
		return
	}

	req := bindBrandSave(c)
	if req.Name == "" {
		helpers.ValidationFailed(c, errors.New("name is required"))
		return
	}

	brand, err := h.service.Update(id, req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Brand updated successfully.", brandresponses.JSON(*brand))
}

// Delete handles DELETE /api/brand/:id.
func (h *BrandHandler) Delete(c *gin.Context) {
	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		helpers.Respond(c, http.StatusForbidden, "Brand not found.", nil)
		return
	}

	if err := h.service.Delete(id); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Brand deleted successfully.", nil)
}

// bindBrandSave binds the brand payload from JSON or multipart. Multipart is
// needed for the image file; Gin's ShouldBind cannot bind a multipart file part
// that shares a name with a string field, so multipart is read manually.
func bindBrandSave(c *gin.Context) brandrequests.SaveRequest {
	var req brandrequests.SaveRequest

	if strings.HasPrefix(c.ContentType(), "multipart/form-data") {
		if v, ok := c.GetPostForm("name"); ok {
			req.Name = strings.TrimSpace(v)
		}
		if path, err := helpers.SaveUpload(c, "image", "brands"); err == nil {
			req.Image = &path
		}
		if v, ok := c.GetPostForm("is_active"); ok && v != "" {
			b := v == "1" || v == "true" || v == "on"
			req.IsActive = &b
		}
		if v, ok := c.GetPostForm("sort_order"); ok && v != "" {
			if n, err := strconv.Atoi(v); err == nil {
				req.SortOrder = &n
			}
		}
		return req
	}

	_ = c.ShouldBindJSON(&req)
	return req
}
