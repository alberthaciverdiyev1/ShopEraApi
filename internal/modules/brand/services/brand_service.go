// Package services holds Brand module business logic.
package services

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	"shopera/internal/modules/brand/models"
	brandrepositories "shopera/internal/modules/brand/repositories"
	brandrequests "shopera/internal/modules/brand/requests"
	brandresponses "shopera/internal/modules/brand/responses"
)

// BrandService holds the brand business logic.
type BrandService struct {
	repo *brandrepositories.BrandRepository
}

func NewBrandService(repo *brandrepositories.BrandRepository) *BrandService {
	return &BrandService{repo: repo}
}

// List returns a paginated brand list.
func (s *BrandService) List(q helpers.Query) (gin.H, error) {
	items, total, err := s.repo.List(q)
	if err != nil {
		return nil, err
	}
	return gin.H{"data": brandresponses.Collection(items), "meta": q.Meta(total)}, nil
}

// Details returns a brand by id.
func (s *BrandService) Details(id int64) (*models.Brand, error) {
	return s.repo.FindByID(id)
}

// Add creates a brand.
func (s *BrandService) Add(req brandrequests.SaveRequest) (*models.Brand, error) {
	brand := &models.Brand{
		Name:     req.Name,
		Image:    req.Image,
		IsActive: true,
	}
	if req.IsActive != nil {
		brand.IsActive = *req.IsActive
	}
	if req.SortOrder != nil {
		brand.SortOrder = *req.SortOrder
	}
	if err := s.repo.Create(brand); err != nil {
		return nil, err
	}
	return brand, nil
}

// Update applies changes to a brand.
func (s *BrandService) Update(id int64, req brandrequests.SaveRequest) (*models.Brand, error) {
	fields := map[string]any{"name": req.Name}
	if req.Image != nil {
		fields["image"] = *req.Image
	}
	if req.IsActive != nil {
		fields["is_active"] = *req.IsActive
	}
	if req.SortOrder != nil {
		fields["sort_order"] = *req.SortOrder
	}
	brand, err := s.repo.Update(id, fields)
	if err != nil {
		return nil, err
	}
	if brand == nil {
		return nil, helpers.NewAppError(403, "Brand not found.")
	}
	return brand, nil
}

// Delete removes a brand (detaching its products).
func (s *BrandService) Delete(id int64) error {
	return s.repo.Delete(id)
}
