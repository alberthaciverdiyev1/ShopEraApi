// Package repositories holds the data access for the location models.
package repositories

import (
	"errors"
	"strings"

	"gorm.io/gorm"

	"shopera/internal/modules/delivery/models"
)

// CityRepository is the data access for cities and their towns.
type CityRepository struct {
	db *gorm.DB
}

func NewCityRepository(db *gorm.DB) *CityRepository { return &CityRepository{db: db} }

// List returns active cities with their active towns.
func (r *CityRepository) List() ([]models.City, error) {
	var items []models.City
	err := r.db.
		Preload("Towns", func(db *gorm.DB) *gorm.DB { return db.Where("is_active = ?", true).Order("name asc") }).
		Where("is_active = ?", true).
		Order("name asc").
		Find(&items).Error
	return items, err
}

// FindMatching resolves a city input (key or name) to an active city.
func (r *CityRepository) FindMatching(value string) (*models.City, error) {
	v := strings.ToLower(strings.TrimSpace(value))
	if v == "" {
		return nil, nil
	}
	var city models.City
	err := r.db.Where("is_active = ?", true).
		Where("lower(key) = ? OR lower(name) = ?", v, v).
		First(&city).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &city, nil
}

// FindByKey returns a city by key (trashed included, for display).
func (r *CityRepository) FindByKey(key string) (*models.City, error) {
	var city models.City
	err := r.db.Unscoped().Where("key = ?", key).First(&city).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &city, nil
}

// MatchTown resolves a town name within a city to its canonical record.
func (r *CityRepository) MatchTown(cityID int64, name string) (*models.CityTown, error) {
	v := strings.ToLower(strings.TrimSpace(name))
	if v == "" {
		return nil, nil
	}
	var town models.CityTown
	err := r.db.Where("city_id = ? AND is_active = ?", cityID, true).
		Where("lower(name) = ?", v).
		First(&town).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &town, nil
}

// HasTowns reports whether a city has any active town.
func (r *CityRepository) HasTowns(cityID int64) (bool, error) {
	var count int64
	err := r.db.Model(&models.CityTown{}).Where("city_id = ? AND is_active = ?", cityID, true).Count(&count).Error
	return count > 0, err
}
