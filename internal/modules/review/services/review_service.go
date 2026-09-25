// Package services holds Review module business logic.
package services

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	"shopera/internal/modules/review/models"
	reviewrepositories "shopera/internal/modules/review/repositories"
	reviewrequests "shopera/internal/modules/review/requests"
	reviewresponses "shopera/internal/modules/review/responses"
)

// ReviewService holds the review business logic.
type ReviewService struct {
	repo *reviewrepositories.ReviewRepository
}

func NewReviewService(repo *reviewrepositories.ReviewRepository) *ReviewService {
	return &ReviewService{repo: repo}
}

// List returns approved reviews of a product, bumping its view counter.
func (s *ReviewService) List(productID int64, q helpers.Query) (gin.H, error) {
	items, total, err := s.repo.ListByProduct(productID, q)
	if err != nil {
		return nil, err
	}
	_ = s.repo.IncrementProductViews(productID)
	return gin.H{"data": reviewresponses.Collection(items), "meta": q.Meta(total)}, nil
}

// ListAdmin returns all reviews (optional status filter).
func (s *ReviewService) ListAdmin(q helpers.Query, status *string) (gin.H, error) {
	items, total, err := s.repo.ListAdmin(status, q)
	if err != nil {
		return nil, err
	}
	return gin.H{"data": reviewresponses.ListCollection(items), "meta": q.Meta(total)}, nil
}

// Add creates a pending review for the given user.
func (s *ReviewService) Add(req reviewrequests.AddRequest, userID int64, imagePath *string) (*models.Review, error) {
	review := &models.Review{
		UserID:    userID,
		ProductID: req.ProductID,
		Rate:      req.Rate,
		Comment:   req.Comment,
		Image:     imagePath,
		Status:    models.StatusPending,
	}
	if err := s.repo.Create(review); err != nil {
		return nil, err
	}
	return s.repo.FindByID(review.ID)
}

// ChangeStatus updates a review's status by name (PENDING/APPROVED/REJECTED).
func (s *ReviewService) ChangeStatus(reviewID int64, statusName string) error {
	value, ok := models.StatusValue(statusName)
	if !ok {
		return helpers.NewAppError(422, "Invalid status. Valid: PENDING, APPROVED, REJECTED.")
	}
	return s.repo.UpdateStatus(reviewID, value)
}

// Delete removes the caller's own review.
func (s *ReviewService) Delete(id, userID int64) error {
	review, err := s.repo.FindByID(id)
	if err != nil {
		return err
	}
	if review == nil {
		return helpers.NewAppError(404, "Review not found.")
	}
	if review.UserID != userID {
		return helpers.NewAppError(403, "You are not allowed to delete this review.")
	}
	return s.repo.Delete(id)
}

// DeleteByAdmin removes any review.
func (s *ReviewService) DeleteByAdmin(id int64) error {
	return s.repo.Delete(id)
}
