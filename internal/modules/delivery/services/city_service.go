// Package services holds Delivery module business logic.
package services

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	deliveryhelpers "shopera/internal/modules/delivery/helpers"
	"shopera/internal/modules/delivery/models"
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

// Options returns active cities as {id, key, name} (for selects).
func (s *CityService) Options() ([]gin.H, error) {
	cities, err := s.repo.List()
	if err != nil {
		return nil, err
	}
	out := make([]gin.H, 0, len(cities))
	for _, c := range cities {
		out = append(out, gin.H{"id": c.Key, "key": c.Key, "name": c.Name})
	}
	return out, nil
}

// Add creates (or restores) a city from its name.
func (s *CityService) Add(name string) (*models.City, error) {
	key := deliveryhelpers.Key(name)
	existing, err := s.repo.FindByKey(key)
	if err != nil {
		return nil, err
	}
	if existing != nil {
		if !existing.DeletedAt.Valid && existing.IsActive {
			return nil, helpers.NewAppError(400, "City already exists.")
		}
		return s.repo.RestoreUpdate(existing.ID, map[string]any{"name": name, "is_active": true})
	}
	city := &models.City{Key: key, Name: name, IsActive: true}
	if err := s.repo.Create(city); err != nil {
		return nil, err
	}
	return city, nil
}

// Update changes a city's name.
func (s *CityService) Update(key, name string) (*models.City, error) {
	city, err := s.repo.FindActiveByKey(key)
	if err != nil {
		return nil, err
	}
	if city == nil {
		return nil, helpers.NewAppError(404, "City not found.")
	}
	return s.repo.Update(city.ID, map[string]any{"name": name})
}

// Delete deactivates and soft-deletes a city.
func (s *CityService) Delete(key string) error {
	city, err := s.repo.FindActiveByKey(key)
	if err != nil {
		return err
	}
	if city == nil {
		return helpers.NewAppError(404, "City Not Found")
	}
	if _, err := s.repo.Update(city.ID, map[string]any{"is_active": false}); err != nil {
		return err
	}
	return s.repo.Delete(city.ID)
}

// Details returns a city by key.
func (s *CityService) Details(key string) (*models.City, error) {
	city, err := s.repo.FindActiveByKey(key)
	if err != nil {
		return nil, err
	}
	if city == nil {
		return nil, helpers.NewAppError(404, "City Not Found")
	}
	return city, nil
}
