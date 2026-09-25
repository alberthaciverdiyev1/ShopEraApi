// Package services holds Favorite module business logic.
package services

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	favoriterepositories "shopera/internal/modules/favorite/repositories"
	productrepositories "shopera/internal/modules/product/repositories"
	productresponses "shopera/internal/modules/product/responses"
)

// FavoriteService holds the favorite business logic.
type FavoriteService struct {
	repo     *favoriterepositories.FavoriteRepository
	products *productrepositories.ProductRepository
}

func NewFavoriteService(repo *favoriterepositories.FavoriteRepository, products *productrepositories.ProductRepository) *FavoriteService {
	return &FavoriteService{repo: repo, products: products}
}

// List returns the user's favorite products.
func (s *FavoriteService) List(userID int64, q helpers.Query, lang string) (gin.H, error) {
	items, total, err := s.repo.ListByUser(userID, q)
	if err != nil {
		return nil, err
	}
	ids := make([]int64, 0, len(items))
	for _, p := range items {
		ids = append(ids, p.ID)
	}
	pivots, err := s.products.SizePivots(ids)
	if err != nil {
		return nil, err
	}
	return gin.H{"data": productresponses.Collection(items, lang, pivots), "meta": q.Meta(total)}, nil
}

// Add toggles the product in the user's favorites; returns the resulting message.
func (s *FavoriteService) Add(userID, productID int64) (string, error) {
	exists, err := s.repo.ProductExists(productID)
	if err != nil {
		return "", err
	}
	if !exists {
		return "", helpers.NewAppError(403, "Product not found.")
	}

	favorited, err := s.repo.Exists(userID, productID)
	if err != nil {
		return "", err
	}
	if favorited {
		if err := s.repo.Remove(userID, productID); err != nil {
			return "", err
		}
		return "Product removed from favorites.", nil
	}
	if err := s.repo.Insert(userID, productID); err != nil {
		return "", err
	}
	return "Product added to favorites.", nil
}

// Delete removes the product from the user's favorites.
func (s *FavoriteService) Delete(userID, productID int64) (string, error) {
	exists, err := s.repo.ProductExists(productID)
	if err != nil {
		return "", err
	}
	if !exists {
		return "", helpers.NewAppError(403, "Product not found.")
	}

	favorited, err := s.repo.Exists(userID, productID)
	if err != nil {
		return "", err
	}
	if !favorited {
		return "Product was not in favorites.", nil
	}
	if err := s.repo.Remove(userID, productID); err != nil {
		return "", err
	}
	return "Product removed from favorites.", nil
}
