// Package handlers holds Category module HTTP handlers.
package handlers

import (
	"net/http"
	"strconv"
	"strings"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	categoryrequests "shopera/internal/modules/category/requests"
	categoryresponses "shopera/internal/modules/category/responses"
	categoryservices "shopera/internal/modules/category/services"
	producthelpers "shopera/internal/modules/product/helpers"
)

// CategoryHandler serves the category endpoints.
type CategoryHandler struct {
	service *categoryservices.CategoryService
}

func NewCategoryHandler(service *categoryservices.CategoryService) *CategoryHandler {
	return &CategoryHandler{service: service}
}

// List handles GET /api/category.
func (h *CategoryHandler) List(c *gin.Context) {
	onlyParents := c.Query("all") == ""
	items, err := h.service.List(helpers.ParseQuery(c), onlyParents)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Categories retrieved successfully.", categoryresponses.Collection(items))
}

// WithProducts handles GET /api/category/with-products.
func (h *CategoryHandler) WithProducts(c *gin.Context) {
	onlyParents := c.Query("all") == ""
	lang := producthelpers.ResolveLocale(c.GetHeader("Accept-Language"))

	items, err := h.service.WithProducts(helpers.ParseQuery(c), onlyParents, lang)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Categories with products retrieved successfully.", items)
}

// ListAdmin handles GET /api/category/admin.
func (h *CategoryHandler) ListAdmin(c *gin.Context) {
	onlyParents := c.Query("all") == ""
	items, err := h.service.List(helpers.ParseQuery(c), onlyParents)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Categories retrieved successfully.", categoryresponses.Collection(items))
}

// Details handles GET /api/category/:id.
func (h *CategoryHandler) Details(c *gin.Context) {
	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		helpers.Respond(c, http.StatusForbidden, "Category not found.", nil)
		return
	}

	item, err := h.service.Details(id)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	if item == nil {
		helpers.Respond(c, http.StatusForbidden, "Category not found.", nil)
		return
	}
	helpers.Respond(c, http.StatusOK, "Category details retrieved successfully.", categoryresponses.JSON(*item))
}

// Add handles POST /api/category.
func (h *CategoryHandler) Add(c *gin.Context) {
	req := bindCategorySave(c)

	category, err := h.service.Add(req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Category added successfully.", categoryresponses.JSON(*category))
}

// Update handles PUT /api/category/:id.
func (h *CategoryHandler) Update(c *gin.Context) {
	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		helpers.Respond(c, http.StatusForbidden, "Category not found.", nil)
		return
	}

	req := bindCategorySave(c)

	category, err := h.service.Update(id, req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Category updated successfully", categoryresponses.JSON(*category))
}

// Delete handles DELETE /api/category/:id.
func (h *CategoryHandler) Delete(c *gin.Context) {
	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		helpers.Respond(c, http.StatusForbidden, "Category not found.", nil)
		return
	}

	if err := h.service.Delete(id); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Category deleted successfully.", nil)
}

// bindCategorySave binds the category payload from JSON or multipart. Multipart
// is needed for the image file and for the translated name map (name[az]=...),
// which Gin's form binder does not populate by itself.
func bindCategorySave(c *gin.Context) categoryrequests.SaveRequest {
	var req categoryrequests.SaveRequest
	if strings.HasPrefix(c.ContentType(), "multipart/form-data") {
		req.Name = collectNameTranslations(c)
		if path, err := helpers.SaveUpload(c, "image", "categories"); err == nil {
			req.Image = &path
		}
		if v, ok := c.GetPostForm("description"); ok && v != "" {
			req.Description = &v
		}
		if v, ok := c.GetPostForm("parent_id"); ok && v != "" {
			if n, err := strconv.ParseInt(v, 10, 64); err == nil {
				req.ParentID = &n
			}
		}
		if v, ok := c.GetPostForm("is_active"); ok && v != "" {
			b := v == "1" || v == "true"
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

// collectNameTranslations reads name[az], name[en], name[ru], name[tr] from the form.
func collectNameTranslations(c *gin.Context) map[string]string {
	out := map[string]string{}
	for _, lang := range []string{"az", "en", "ru", "tr"} {
		if v, ok := c.GetPostForm("name[" + lang + "]"); ok && v != "" {
			out[lang] = v
		}
	}
	if len(out) == 0 {
		return nil
	}
	return out
}
