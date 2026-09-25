package product

import (
	"shopera/internal/modules/product/models"
	"shopera/internal/modules/product/repositories"
)

// ProductService holds the product business logic.
type ProductService struct {
	repo *repositories.ProductRepository
}

func NewProductService(repo *repositories.ProductRepository) *ProductService {
	return &ProductService{repo: repo}
}

// List returns the paginated product list.
func (s *ProductService) List(filter Filter, lang string) (*ListResult, error) {
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
func (s *ProductService) Details(id int64, lang string) (map[string]any, error) {
	p, err := s.repo.FindByID(id)
	if err != nil || p == nil {
		return nil, err
	}
	return map[string]any(JSON(*p, lang)), nil
}

// Create builds a product from the request and stores it.
func (s *ProductService) Create(req CreateRequest) (*models.Product, error) {
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
