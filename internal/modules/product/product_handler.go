package product

import (
	"net/http"
	"strconv"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
)

// ProductHandler serves the product endpoints.
type ProductHandler struct {
	service *ProductService
}

func NewProductHandler(service *ProductService) *ProductHandler {
	return &ProductHandler{service: service}
}

// List handles GET /api/product.
func (h *ProductHandler) List(c *gin.Context) {
	filter := Filter{
		Page:    queryInt(c, "page", 1),
		PerPage: queryInt(c, "per_page", 20),
	}
	lang := ResolveLocale(c.GetHeader("Accept-Language"))

	result, err := h.service.List(filter, lang)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "", result)
}

// Details handles GET /api/product/:id.
func (h *ProductHandler) Details(c *gin.Context) {
	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Product not found", nil)
		return
	}

	lang := ResolveLocale(c.GetHeader("Accept-Language"))
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

// Create handles POST /api/product/add (requires auth).
func (h *ProductHandler) Create(c *gin.Context) {
	var req CreateRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}

	product, err := h.service.Create(req)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusCreated, "Product created", JSON(*product, "az"))
}

func queryInt(c *gin.Context, key string, fallback int) int {
	if n, err := strconv.Atoi(c.Query(key)); err == nil && n > 0 {
		return n
	}
	return fallback
}
