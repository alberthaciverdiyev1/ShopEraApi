// Package services holds Filter module business logic.
package services

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	filterhelpers "shopera/internal/modules/filter/helpers"
	filtermodels "shopera/internal/modules/filter/models"
	filterrepositories "shopera/internal/modules/filter/repositories"
	filterrequests "shopera/internal/modules/filter/requests"
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

// Create adds a filter.
func (s *FilterService) Create(req filterrequests.SaveRequest) (*filtermodels.Filter, error) {
	filter := &filtermodels.Filter{
		Title:   filterhelpers.FillLower(req.Title),
		Type:    req.Type,
		Options: req.Options,
	}
	if err := s.repo.Create(filter); err != nil {
		return nil, err
	}
	return filter, nil
}

// Update changes a filter.
func (s *FilterService) Update(id int64, req filterrequests.SaveRequest) (*filtermodels.Filter, error) {
	fields := map[string]any{
		"title":   filterhelpers.FillLower(req.Title),
		"type":    req.Type,
		"options": req.Options,
	}
	filter, err := s.repo.Update(id, fields)
	if err != nil {
		return nil, err
	}
	if filter == nil {
		return nil, helpers.NewAppError(404, "Filter not found.")
	}
	return filter, nil
}

// Delete removes a filter.
func (s *FilterService) Delete(id int64) error {
	existing, err := s.repo.FindByID(id)
	if err != nil {
		return err
	}
	if existing == nil {
		return helpers.NewAppError(404, "Filter not found.")
	}
	return s.repo.Delete(id)
}

// Details returns one filter with its category ids.
func (s *FilterService) Details(id int64) (gin.H, error) {
	filter, err := s.repo.FindByID(id)
	if err != nil {
		return nil, err
	}
	if filter == nil {
		return nil, helpers.NewAppError(404, "Filter not found.")
	}
	categories, err := s.repo.CategoryIDs(id)
	if err != nil {
		return nil, err
	}
	out := filterresponses.JSON(*filter, nil)
	out["category_ids"] = categories
	return out, nil
}

// SetCategories replaces a filter's category attachments.
func (s *FilterService) SetCategories(req filterrequests.CategoryAssignRequest) error {
	existing, err := s.repo.FindByID(req.FilterID)
	if err != nil {
		return err
	}
	if existing == nil {
		return helpers.NewAppError(404, "Filter not found.")
	}
	return s.repo.SetCategories(req.FilterID, req.CategoryIDs)
}

// ProductValues returns a product's filter values.
func (s *FilterService) ProductValues(productID int64) ([]gin.H, error) {
	items, err := s.repo.ProductValues(productID)
	if err != nil {
		return nil, err
	}
	out := make([]gin.H, 0, len(items))
	for _, item := range items {
		out = append(out, gin.H{"filter_id": item.FilterID, "value": item.Value})
	}
	return out, nil
}

// SetProductValues replaces a product's filter values.
func (s *FilterService) SetProductValues(req filterrequests.ProductValuesRequest) error {
	items := make([]filtermodels.ProductFilter, 0, len(req.Values))
	for _, v := range req.Values {
		value := v.Value
		items = append(items, filtermodels.ProductFilter{FilterID: v.FilterID, Value: &value})
	}
	return s.repo.SetProductValues(req.ProductID, items)
}
