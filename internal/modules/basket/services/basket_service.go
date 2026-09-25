// Package services holds Basket module business logic.
package services

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	basketmodels "shopera/internal/modules/basket/models"
	basketrepositories "shopera/internal/modules/basket/repositories"
	basketrequests "shopera/internal/modules/basket/requests"
	basketresponses "shopera/internal/modules/basket/responses"
	productmodels "shopera/internal/modules/product/models"
	productrepositories "shopera/internal/modules/product/repositories"
)

// BasketService holds the basket business logic.
type BasketService struct {
	repo     *basketrepositories.BasketRepository
	products *productrepositories.ProductRepository
}

func NewBasketService(repo *basketrepositories.BasketRepository, products *productrepositories.ProductRepository) *BasketService {
	return &BasketService{repo: repo, products: products}
}

// List returns the user's basket.
func (s *BasketService) List(userID int64, q helpers.Query, lang string) (gin.H, error) {
	items, err := s.repo.List(userID, q)
	if err != nil {
		return nil, err
	}
	ids := make([]int64, 0, len(items))
	for _, b := range items {
		ids = append(ids, b.ProductID)
	}
	pivots, err := s.products.SizePivots(ids)
	if err != nil {
		return nil, err
	}
	return gin.H{"data": basketresponses.Collection(items, lang, pivots)}, nil
}

// Add validates and inserts a basket item.
func (s *BasketService) Add(userID int64, req basketrequests.AddRequest) error {
	product, err := s.repo.ProductForBasket(req.ProductID)
	if err != nil {
		return err
	}
	if product == nil {
		return helpers.NewAppError(403, "Product not found.")
	}
	if req.Quantity > product.StockCount {
		return helpers.NewAppError(403, "Only the available stock can be added.")
	}

	if limit := product.PurchaseLimit; limit != nil && *limit > 0 {
		current, err := s.repo.QuantitySum(userID, product.ID)
		if err != nil {
			return err
		}
		if req.Quantity > *limit-current {
			return helpers.NewAppError(403, "The maximum purchase limit for this product has been reached.")
		}
	}

	colorID := req.ColorID
	sizeID := req.SizeID
	if len(product.Colors) == 0 {
		colorID = nil
	}
	if len(product.Sizes) == 0 {
		sizeID = nil
	}
	if colorID != nil && !hasColor(product, *colorID) {
		return helpers.NewAppError(403, "Selected color is not available for this product.")
	}
	if sizeID != nil && !hasSize(product, *sizeID) {
		return helpers.NewAppError(403, "Selected size is not available for this product.")
	}

	return s.repo.Create(&basketmodels.Basket{
		UserID:    userID,
		ProductID: product.ID,
		Quantity:  req.Quantity,
		ColorID:   colorID,
		SizeID:    sizeID,
		Gender:    req.Gender,
	})
}

// Update changes quantity/options of the user's basket item.
func (s *BasketService) Update(userID, id int64, req basketrequests.UpdateRequest) error {
	basket, err := s.repo.FindOwned(userID, id)
	if err != nil {
		return err
	}
	if basket == nil {
		return helpers.NewAppError(403, "Basket not found.")
	}

	fields := map[string]any{}
	if req.Quantity != nil {
		if basket.Product != nil && *req.Quantity > basket.Product.StockCount {
			return helpers.NewAppError(403, "Only the available stock can be added.")
		}
		if basket.Product != nil && basket.Product.PurchaseLimit != nil && *basket.Product.PurchaseLimit > 0 && *req.Quantity > *basket.Product.PurchaseLimit {
			return helpers.NewAppError(403, "The maximum purchase limit for this product has been reached.")
		}
		fields["quantity"] = *req.Quantity
	}
	if req.ColorID != nil {
		fields["color_id"] = *req.ColorID
	}
	if req.SizeID != nil {
		fields["size_id"] = *req.SizeID
	}
	if req.Gender != nil {
		fields["gender"] = *req.Gender
	}
	if req.Selected != nil {
		fields["selected"] = *req.Selected
	}

	return s.repo.Update(id, fields)
}

// Delete removes the user's basket item.
func (s *BasketService) Delete(userID, id int64) error {
	basket, err := s.repo.FindOwned(userID, id)
	if err != nil {
		return err
	}
	if basket == nil {
		return helpers.NewAppError(403, "Basket not found.")
	}
	return s.repo.Delete(id)
}

func hasColor(product *productmodels.Product, colorID int64) bool {
	for _, c := range product.Colors {
		if c.ID == colorID {
			return true
		}
	}
	return false
}

func hasSize(product *productmodels.Product, sizeID int64) bool {
	for _, s := range product.Sizes {
		if s.ID == sizeID {
			return true
		}
	}
	return false
}
