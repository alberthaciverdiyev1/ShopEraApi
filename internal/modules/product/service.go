package product

import (
	"shopera/internal/modules/product/models"
	"shopera/internal/modules/product/repositories"
)

// Service holds the product business logic.
type Service struct {
	repo *repositories.ProductRepository
}

func NewService(repo *repositories.ProductRepository) *Service { return &Service{repo: repo} }

// List returns the paginated product list.
func (s *Service) List(filter Filter, lang string) (*ListResult, error) {
	products, total, err := s.repo.List(filter.Page, filter.PerPage, filter.StoreID)
	if err != nil {
		return nil, err
	}

	data := make([]map[string]any, 0, len(products))
	for _, p := range products {
		data = append(data, map[string]any(JSON(p, lang)))
	}

	return &ListResult{
		Data: data,
		Meta: map[string]any{"total": total, "page": filter.Page, "per_page": filter.PerPage},
	}, nil
}

// Details returns a single product shape, or nil when not found.
func (s *Service) Details(id int64, lang string) (map[string]any, error) {
	p, err := s.repo.FindByID(id)
	if err != nil || p == nil {
		return nil, err
	}
	return map[string]any(JSON(*p, lang)), nil
}

// Create builds a product from the request and stores it.
func (s *Service) Create(req CreateRequest) (*models.Product, error) {
	product := &models.Product{
		Title:      req.Title,
		Price:      req.Price,
		StockCount: req.StockCount,
		IsActive:   req.IsActive,
	}
	if err := s.repo.Create(product); err != nil {
		return nil, err
	}
	return product, nil
}
