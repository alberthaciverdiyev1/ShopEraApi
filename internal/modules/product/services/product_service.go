// Package services holds Product module business logic.
package services

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	"shopera/internal/modules/product/models"
	productrepositories "shopera/internal/modules/product/repositories"
	productrequests "shopera/internal/modules/product/requests"
	productresponses "shopera/internal/modules/product/responses"
)

// ProductService holds the product business logic.
type ProductService struct {
	repo *productrepositories.ProductRepository
}

func NewProductService(repo *productrepositories.ProductRepository) *ProductService {
	return &ProductService{repo: repo}
}

// List returns the paginated product list.
func (s *ProductService) List(q helpers.Query, lang string) (gin.H, error) {
	items, total, err := s.repo.List(q)
	if err != nil {
		return nil, err
	}
	return gin.H{"data": productresponses.Collection(items, lang), "meta": q.Meta(total)}, nil
}

// Details returns a single product shape, or nil when not found.
func (s *ProductService) Details(id int64, lang string) (gin.H, error) {
	p, err := s.repo.FindByID(id)
	if err != nil || p == nil {
		return nil, err
	}
	return productresponses.JSON(*p, lang), nil
}

// Create builds a product from the request and stores it.
func (s *ProductService) Create(req productrequests.CreateRequest) (*models.Product, error) {
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
