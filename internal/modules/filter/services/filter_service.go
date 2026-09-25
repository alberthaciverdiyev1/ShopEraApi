// Package services holds Filter module business logic.
package services

import (
	"github.com/gin-gonic/gin"

	filterrepositories "shopera/internal/modules/filter/repositories"
	filterresponses "shopera/internal/modules/filter/responses"
)

// FilterService holds the dynamic-filter business logic.
type FilterService struct {
	repo *filterrepositories.FilterRepository
}

func NewFilterService(repo *filterrepositories.FilterRepository) *FilterService {
	return &FilterService{repo: repo}
}

// List returns every filter.
func (s *FilterService) List() ([]gin.H, error) {
	items, err := s.repo.All()
	if err != nil {
		return nil, err
	}
	out := make([]gin.H, 0, len(items))
	for _, f := range items {
		out = append(out, filterresponses.JSON(f, nil))
	}
	return out, nil
}

// CategoryFilters returns a category's filters with the values found in its products.
func (s *FilterService) CategoryFilters(categoryID int64) ([]gin.H, error) {
	filters, err := s.repo.ByCategory(categoryID)
	if err != nil {
		return nil, err
	}
	values, err := s.repo.CategoryValues(categoryID)
	if err != nil {
		return nil, err
	}

	out := make([]gin.H, 0, len(filters))
	for _, f := range filters {
		out = append(out, filterresponses.JSON(f, values[f.ID]))
	}
	return out, nil
}
