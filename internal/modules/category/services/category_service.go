// Package services holds Category module business logic.
package services

import (
	"shopera/internal/helpers"
	categoryhelpers "shopera/internal/modules/category/helpers"
	"shopera/internal/modules/category/models"
	categoryrepositories "shopera/internal/modules/category/repositories"
	categoryrequests "shopera/internal/modules/category/requests"
)

// CategoryService holds the category business logic.
type CategoryService struct {
	repo *categoryrepositories.CategoryRepository
}

func NewCategoryService(repo *categoryrepositories.CategoryRepository) *CategoryService {
	return &CategoryService{repo: repo}
}

// List returns categories; onlyParents is used for the public endpoint.
func (s *CategoryService) List(query helpers.Query, onlyParents bool) ([]models.Category, error) {
	return s.repo.List(query, onlyParents)
}

// Details returns a category by id.
func (s *CategoryService) Details(id int64) (*models.Category, error) {
	return s.repo.FindByID(id)
}

// Add creates a category, filling translations.
func (s *CategoryService) Add(req categoryrequests.SaveRequest) (*models.Category, error) {
	if req.Name == nil || req.Name["az"] == "" {
		return nil, helpers.NewAppError(422, "The name.az field is required.")
	}

	category := &models.Category{
		Name:        categoryhelpers.FillTranslations(req.Name),
		Image:       req.Image,
		Description: req.Description,
		ParentID:    req.ParentID,
		IsActive:    true,
	}
	if req.IsActive != nil {
		category.IsActive = *req.IsActive
	}
	if req.SortOrder != nil {
		category.SortOrder = *req.SortOrder
	}

	if err := s.repo.Create(category); err != nil {
		return nil, err
	}
	return category, nil
}

// Update applies changes to a category.
func (s *CategoryService) Update(id int64, req categoryrequests.SaveRequest) (*models.Category, error) {
	fields := map[string]any{}
	if req.Image != nil {
		fields["image"] = *req.Image
	}
	if req.Description != nil {
		fields["description"] = *req.Description
	}
	if req.ParentID != nil {
		fields["parent_id"] = *req.ParentID
	}
	if req.IsActive != nil {
		fields["is_active"] = *req.IsActive
	}

	var name map[string]string
	if len(req.Name) > 0 {
		name = categoryhelpers.FillTranslations(req.Name)
	}

	return s.repo.Update(id, fields, name, req.SortOrder)
}

// Delete removes a category (detaching its products).
func (s *CategoryService) Delete(id int64) error {
	return s.repo.Delete(id)
}
