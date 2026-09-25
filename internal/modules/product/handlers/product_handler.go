// Package handlers holds Product module HTTP handlers.
package handlers

import (
	"mime/multipart"
	"net/http"
	"regexp"
	"strconv"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	producthelpers "shopera/internal/modules/product/helpers"
	productrepositories "shopera/internal/modules/product/repositories"
	productrequests "shopera/internal/modules/product/requests"
	productresponses "shopera/internal/modules/product/responses"
	productservices "shopera/internal/modules/product/services"
)

var (
	imageFileKey = regexp.MustCompile(`^images\[(\d+)\]\[file\]$`)
	videoFileKey = regexp.MustCompile(`^videos\[(\d*)\]$`)
)

// ProductHandler serves the product endpoints.
type ProductHandler struct {
	service *productservices.ProductService
}

func NewProductHandler(service *productservices.ProductService) *ProductHandler {
	return &ProductHandler{service: service}
}

// List handles GET /api/product.
func (h *ProductHandler) List(c *gin.Context) {
	lang := producthelpers.ResolveLocale(c.GetHeader("Accept-Language"))
	result, err := h.service.List(helpers.ParseQuery(c), lang)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Products retrieved successfully.", result)
}

// Details handles GET /api/product/:id.
func (h *ProductHandler) Details(c *gin.Context) {
	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Product not found", nil)
		return
	}

	lang := producthelpers.ResolveLocale(c.GetHeader("Accept-Language"))
	item, err := h.service.Details(id, lang)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	if item == nil {
		helpers.Respond(c, http.StatusNotFound, "Product not found", nil)
		return
	}
	helpers.Respond(c, http.StatusOK, "", item)
}

// Add handles POST /api/product/add (multipart).
func (h *ProductHandler) Add(c *gin.Context) {
	form, err := c.MultipartForm()
	if err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	req := productrequests.FromMultipart(form)
	images, videos := collectUploads(c, form)

	product, err := h.service.Add(req, images, videos)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Product added successfully.", productresponses.JSON(*product, "az"))
}

// Update handles PUT /api/product/:id (multipart).
func (h *ProductHandler) Update(c *gin.Context) {
	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Product not found", nil)
		return
	}
	form, err := c.MultipartForm()
	if err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	req := productrequests.FromMultipart(form)
	images, videos := collectUploads(c, form)

	product, err := h.service.Update(id, req, images, videos)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Product updated successfully.", productresponses.JSON(*product, "az"))
}

// Delete handles DELETE /api/product/:id.
func (h *ProductHandler) Delete(c *gin.Context) {
	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Product not found", nil)
		return
	}
	if err := h.service.Delete(id); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Product deleted successfully.", nil)
}

// collectUploads saves image/video files and pairs images with their color tags.
func collectUploads(c *gin.Context, form *multipart.Form) ([]productrepositories.ImageCreate, []string) {
	images := []productrepositories.ImageCreate{}
	for key := range form.File {
		m := imageFileKey.FindStringSubmatch(key)
		if m == nil {
			continue
		}
		path, err := helpers.SaveUpload(c, key, "products")
		if err != nil {
			continue
		}
		colorID := colorIDFor(form, m[1])
		images = append(images, productrepositories.ImageCreate{Path: path, ColorID: colorID})
	}

	videos := []string{}
	for key := range form.File {
		if !videoFileKey.MatchString(key) {
			continue
		}
		if path, err := helpers.SaveUpload(c, key, "videos"); err == nil {
			videos = append(videos, path)
		}
	}
	return images, videos
}

func colorIDFor(form *multipart.Form, index string) *int64 {
	values := form.Value["images["+index+"][color_id]"]
	if len(values) == 0 || values[0] == "" {
		return nil
	}
	if n, err := strconv.ParseInt(values[0], 10, 64); err == nil {
		return &n
	}
	return nil
}
