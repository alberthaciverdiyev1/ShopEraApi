// Package handlers holds Review module HTTP handlers.
package handlers

import (
	"net/http"
	"strconv"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	reviewrequests "shopera/internal/modules/review/requests"
	reviewresponses "shopera/internal/modules/review/responses"
	reviewservices "shopera/internal/modules/review/services"
)

// ReviewHandler serves the review endpoints.
type ReviewHandler struct {
	service *reviewservices.ReviewService
}

func NewReviewHandler(service *reviewservices.ReviewService) *ReviewHandler {
	return &ReviewHandler{service: service}
}

// List handles GET /api/review/:product_id.
func (h *ReviewHandler) List(c *gin.Context) {
	productID, err := strconv.ParseInt(c.Param("product_id"), 10, 64)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Reviews not found.", nil)
		return
	}
	result, err := h.service.List(productID, helpers.ParseQuery(c))
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Reviews retrieved successfully.", result)
}

// ListAdmin handles GET /api/review/list-admin.
func (h *ReviewHandler) ListAdmin(c *gin.Context) {
	var status *string
	if v, ok := c.GetQuery("status"); ok && v != "" {
		status = &v
	}
	result, err := h.service.ListAdmin(helpers.ParseQuery(c), status)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Reviews retrieved successfully.", result)
}

// Add handles POST /api/review (multipart or JSON, image optional).
func (h *ReviewHandler) Add(c *gin.Context) {
	var req reviewrequests.AddRequest
	if err := c.ShouldBind(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}

	var imagePath *string
	if form, err := c.MultipartForm(); err == nil {
		if _, ok := form.File["image"]; ok {
			if path, err := helpers.SaveUpload(c, "image", "reviews"); err == nil {
				imagePath = &path
			}
		}
	}

	userID := c.GetInt64("userID")
	review, err := h.service.Add(req, userID, imagePath)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Review added successfully.", reviewresponses.JSON(*review))
}

// ChangeStatus handles PUT /api/review/change-status.
func (h *ReviewHandler) ChangeStatus(c *gin.Context) {
	var req reviewrequests.ChangeStatusRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	if err := h.service.ChangeStatus(req.ReviewID, req.Status); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Review status updated successfully.", nil)
}

// Delete handles DELETE /api/review/:id (own review).
func (h *ReviewHandler) Delete(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Review not found.", nil)
		return
	}
	userID := c.GetInt64("userID")
	if err := h.service.Delete(id, userID); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Review deleted successfully.", nil)
}

// DeleteByAdmin handles DELETE /api/review/admin/:id.
func (h *ReviewHandler) DeleteByAdmin(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Review not found.", nil)
		return
	}
	if err := h.service.DeleteByAdmin(id); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Review deleted successfully.", nil)
}
