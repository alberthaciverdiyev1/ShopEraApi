// Package services holds Size module business logic.
package services

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	sizemodels "shopera/internal/modules/size/models"
	sizerepositories "shopera/internal/modules/size/repositories"
	sizerequests "shopera/internal/modules/size/requests"
	sizeresponses "shopera/internal/modules/size/responses"
)

// SizeService holds the size business logic.
type SizeService struct {
	repo *sizerepositories.SizeRepository
}

func NewSizeService(repo *sizerepositories.SizeRepository) *SizeService {
	return &SizeService{repo: repo}
}

// List returns a paginated size list.
func (s *SizeService) List(q helpers.Query) (gin.H, error) {
	items, total, err := s.repo.List(q)
	if err != nil {
		return nil, err
	}
	return gin.H{"data": sizeresponses.Collection(items), "meta": q.Meta(total)}, nil
}

// Details returns a size by id.
func (s *SizeService) Details(id int64) (*sizemodels.Size, error) {
	return s.repo.FindByID(id)
}

// Add creates a size with an auto-incremented sort_order.
func (s *SizeService) Add(req sizerequests.SaveRequest) (*sizemodels.Size, error) {
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

	size := &sizemodels.Size{Name: req.Name, Icon: req.Icon, IsActive: true, SortOrder: maxOrder + 1}
	if req.IsActive != nil {
		size.IsActive = *req.IsActive
	}
	if err := s.repo.Create(size); err != nil {
		return nil, err
	}
	return size, nil
}

// Update applies changes to a size.
func (s *SizeService) Update(id int64, req sizerequests.SaveRequest) (*sizemodels.Size, error) {
	taken, err := s.repo.ExistsByName(req.Name, id)
	if err != nil {
		return nil, err
	}
	if taken {
		return nil, helpers.NewAppError(422, "The name has already been taken.")
	}

	fields := map[string]any{"name": req.Name}
	if req.Icon != nil {
		fields["icon"] = *req.Icon
	}
	if req.IsActive != nil {
		fields["is_active"] = *req.IsActive
	}
	if req.SortOrder != nil {
		fields["sort_order"] = *req.SortOrder
	}

	size, err := s.repo.Update(id, fields)
	if err != nil {
		return nil, err
	}
	if size == nil {
		return nil, helpers.NewAppError(403, "Size not found.")
	}
	return size, nil
}

// Delete removes a size.
func (s *SizeService) Delete(id int64) error {
	return s.repo.Delete(id)
}
