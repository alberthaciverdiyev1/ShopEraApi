// Package services holds Delivery module business logic.
package services

import (
	"github.com/gin-gonic/gin"

	cityrepositories "shopera/internal/modules/delivery/repositories"
	cityresponses "shopera/internal/modules/delivery/responses"
)

// CityService holds the location business logic.
type CityService struct {
	repo *cityrepositories.CityRepository
}

func NewCityService(repo *cityrepositories.CityRepository) *CityService {
	return &CityService{repo: repo}
}

// List returns active cities with their towns.
func (s *CityService) List() ([]gin.H, error) {
	cities, err := s.repo.List()
	if err != nil {
		return nil, err
	}
	out := make([]gin.H, 0, len(cities))
	for _, c := range cities {
		out = append(out, cityresponses.CityJSON(c))
	}
	return out, nil
}
