// Package services holds Color module business logic.
package services

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	"shopera/internal/modules/color/models"
	colorrepositories "shopera/internal/modules/color/repositories"
	colorrequests "shopera/internal/modules/color/requests"
	colorresponses "shopera/internal/modules/color/responses"
)

// ColorService holds the color business logic.
type ColorService struct {
	repo *colorrepositories.ColorRepository
}

func NewColorService(repo *colorrepositories.ColorRepository) *ColorService {
	return &ColorService{repo: repo}
}

// List returns a paginated color list.
func (s *ColorService) List(q helpers.Query) (gin.H, error) {
	items, total, err := s.repo.List(q)
	if err != nil {
		return nil, err
	}
	return gin.H{"data": colorresponses.Collection(items), "meta": q.Meta(total)}, nil
}

// Details returns a color by id.
func (s *ColorService) Details(id int64) (*models.Color, error) {
	return s.repo.FindByID(id)
}

// Add creates a color with an auto-incremented sort_order.
func (s *ColorService) Add(req colorrequests.SaveRequest) (*models.Color, error) {
	taken, err := s.repo.ExistsByName(req.Name, 0)
	if err != nil {
		return nil, err
	}
	if taken {
		return nil, helpers.NewAppError(422, "The name has already been taken.")
	}

	maxOrder, err := s.repo.MaxSortOrder()
	if err != nil {
		return nil, err
	}

	color := &models.Color{Name: req.Name, Hex: req.Hex, IsActive: true, SortOrder: maxOrder + 1}
	if req.IsActive != nil {
		color.IsActive = *req.IsActive
	}
	if err := s.repo.Create(color); err != nil {
		return nil, err
	}
	return color, nil
}

// Update applies changes to a color.
func (s *ColorService) Update(id int64, req colorrequests.SaveRequest) (*models.Color, error) {
	taken, err := s.repo.ExistsByName(req.Name, id)
	if err != nil {
		return nil, err
	}
	if taken {
		return nil, helpers.NewAppError(422, "The name has already been taken.")
	}

	fields := map[string]any{"name": req.Name}
	if req.Hex != nil {
		fields["hex"] = *req.Hex
	}
	if req.IsActive != nil {
		fields["is_active"] = *req.IsActive
	}
	if req.SortOrder != nil {
		fields["sort_order"] = *req.SortOrder
	}

	color, err := s.repo.Update(id, fields)
	if err != nil {
		return nil, err
	}
	if color == nil {
		return nil, helpers.NewAppError(403, "Colors not found.")
	}
	return color, nil
}

// Delete removes a color (detaching products).
func (s *ColorService) Delete(id int64) error {
	return s.repo.Delete(id)
}
