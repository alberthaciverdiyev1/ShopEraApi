// Package services holds Category module business logic.
package services

import (
	"sort"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	categoryhelpers "shopera/internal/modules/category/helpers"
	"shopera/internal/modules/category/models"
	categoryrepositories "shopera/internal/modules/category/repositories"
	categoryrequests "shopera/internal/modules/category/requests"
	categoryresponses "shopera/internal/modules/category/responses"
	productmodels "shopera/internal/modules/product/models"
	productrepositories "shopera/internal/modules/product/repositories"
	productresponses "shopera/internal/modules/product/responses"
)

// productsPerCategory caps the products embedded in each category (Laravel: 10).
const productsPerCategory = 10

// CategoryService holds the category business logic.
type CategoryService struct {
	repo     *categoryrepositories.CategoryRepository
	products *productrepositories.ProductRepository
}

func NewCategoryService(repo *categoryrepositories.CategoryRepository, products *productrepositories.ProductRepository) *CategoryService {
	return &CategoryService{repo: repo, products: products}
}

// List returns categories; onlyParents is used for the public endpoint.
func (s *CategoryService) List(query helpers.Query, onlyParents bool) ([]models.Category, error) {
	return s.repo.List(query, onlyParents)
}

// WithProducts returns categories with their children (one level) and up to ten
// publicly-available products from each category and its descendants (Laravel
// CategoryService::listWithProducts).
func (s *CategoryService) WithProducts(query helpers.Query, onlyParents bool, lang string) ([]gin.H, error) {
	categories, err := s.repo.List(query, onlyParents)
	if err != nil {
		return nil, err
	}
	if len(categories) == 0 {
		return []gin.H{}, nil
	}

	all, err := s.repo.All()
	if err != nil {
		return nil, err
	}
	childrenByParent := map[int64][]models.Category{}
	for _, c := range all {
		if c.ParentID != nil {
			childrenByParent[*c.ParentID] = append(childrenByParent[*c.ParentID], c)
		}
	}

	descendants := func(rootID int64) []int64 {
		var out []int64
		var walk func(id int64)
		walk = func(id int64) {
			for _, child := range childrenByParent[id] {
				out = append(out, child.ID)
				walk(child.ID)
			}
		}
		walk(rootID)
		return out
	}

	// Fetch products for every involved category in one query.
	idSet := map[int64]bool{}
	for _, root := range categories {
		idSet[root.ID] = true
		for _, id := range descendants(root.ID) {
			idSet[id] = true
		}
	}
	ids := make([]int64, 0, len(idSet))
	for id := range idSet {
		ids = append(ids, id)
	}

	allProducts, err := s.products.PublicByCategoryIDs(ids)
	if err != nil {
		return nil, err
	}
	productsByCategory := map[int64][]productmodels.Product{}
	for _, p := range allProducts {
		if p.CategoryID != nil {
			productsByCategory[*p.CategoryID] = append(productsByCategory[*p.CategoryID], p)
		}
	}

	// Select products per root category (self + descendants, top N by sales).
	selectedByRoot := map[int64][]productmodels.Product{}
	var selectedIDs []int64
	for _, root := range categories {
		seen := map[int64]bool{}
		var selected []productmodels.Product
		for _, id := range append([]int64{root.ID}, descendants(root.ID)...) {
			for _, p := range productsByCategory[id] {
				if !seen[p.ID] {
					seen[p.ID] = true
					selected = append(selected, p)
				}
			}
		}
		sort.SliceStable(selected, func(i, j int) bool { return selected[i].SalesCount > selected[j].SalesCount })
		if len(selected) > productsPerCategory {
			selected = selected[:productsPerCategory]
		}
		selectedByRoot[root.ID] = selected
		for _, p := range selected {
			selectedIDs = append(selectedIDs, p.ID)
		}
	}

	pivots, err := s.products.SizePivots(selectedIDs)
	if err != nil {
		return nil, err
	}

	// Laravel orders roots by their children count, descending.
	sort.SliceStable(categories, func(i, j int) bool {
		return len(childrenByParent[categories[i].ID]) > len(childrenByParent[categories[j].ID])
	})

	out := make([]gin.H, 0, len(categories))
	for _, root := range categories {
		payload := categoryresponses.JSON(root, lang)
		payload["children"] = categoryresponses.Collection(childrenByParent[root.ID], lang)
		payload["products"] = productresponses.Collection(selectedByRoot[root.ID], lang, pivots)
		out = append(out, payload)
	}
	return out, nil
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
