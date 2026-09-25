// Package services holds Address module business logic.
package services

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	addressmodels "shopera/internal/modules/address/models"
	addressrepositories "shopera/internal/modules/address/repositories"
	addressrequests "shopera/internal/modules/address/requests"
	addressresponses "shopera/internal/modules/address/responses"
	cityrepositories "shopera/internal/modules/delivery/repositories"
)

// AddressService holds the address business logic.
type AddressService struct {
	repo   *addressrepositories.AddressRepository
	cities *cityrepositories.CityRepository
}

func NewAddressService(repo *addressrepositories.AddressRepository, cities *cityrepositories.CityRepository) *AddressService {
	return &AddressService{repo: repo, cities: cities}
}

// List returns the user's addresses.
func (s *AddressService) List(userID int64) ([]gin.H, error) {
	items, err := s.repo.ListByUser(userID)
	if err != nil {
		return nil, err
	}
	out := make([]gin.H, 0, len(items))
	for _, a := range items {
		out = append(out, addressresponses.JSON(a, s.cityName(a.City)))
	}
	return out, nil
}

// Details returns one of the user's addresses (nil when not found).
func (s *AddressService) Details(userID, id int64) (gin.H, error) {
	a, err := s.repo.FindOwned(userID, id)
	if err != nil || a == nil {
		return nil, err
	}
	return addressresponses.JSON(*a, s.cityName(a.City)), nil
}

// Add validates the city/town and inserts an address (default when first).
func (s *AddressService) Add(userID int64, req addressrequests.SaveRequest) (*addressmodels.Address, error) {
	city, town, err := s.resolveLocation(req.City, req.TownVillageDistrict)
	if err != nil {
		return nil, err
	}

	hasAny, err := s.repo.HasAny(userID)
	if err != nil {
		return nil, err
	}

	address := &addressmodels.Address{
		UserID:               userID,
		City:                 city.Key,
		TownVillageDistrict:  town,
		StreetBuildingNumber: req.StreetBuildingNumber,
		UnitFloorApartment:   req.UnitFloorApartment,
		FullName:             req.FullName,
		ContactNumber:        req.ContactNumber,
		IsDefault:            !hasAny,
		Latitude:             req.Latitude,
		Longitude:            req.Longitude,
		LocationLabel:        req.LocationLabel,
	}
	if err := s.repo.Create(address); err != nil {
		return nil, err
	}
	return address, nil
}

// Update changes one of the user's addresses.
func (s *AddressService) Update(userID, id int64, req addressrequests.SaveRequest) (*addressmodels.Address, error) {
	existing, err := s.repo.FindOwned(userID, id)
	if err != nil {
		return nil, err
	}
	if existing == nil {
		return nil, helpers.NewAppError(403, "Address not found.")
	}

	city, town, err := s.resolveLocation(req.City, req.TownVillageDistrict)
	if err != nil {
		return nil, err
	}

	if req.IsDefault != nil && *req.IsDefault {
		if err := s.repo.UnsetOtherDefaults(userID, id); err != nil {
			return nil, err
		}
	}

	fields := map[string]any{
		"city":                   city.Key,
		"town_village_district":  town,
		"street_building_number": req.StreetBuildingNumber,
		"unit_floor_apartment":   req.UnitFloorApartment,
		"full_name":              req.FullName,
		"contact_number":         req.ContactNumber,
		"latitude":               req.Latitude,
		"longitude":              req.Longitude,
		"location_label":         req.LocationLabel,
	}
	if req.IsDefault != nil {
		fields["is_default"] = *req.IsDefault
	}

	return s.repo.Update(id, fields)
}

// Delete removes one of the user's addresses.
func (s *AddressService) Delete(userID, id int64) error {
	existing, err := s.repo.FindOwned(userID, id)
	if err != nil {
		return err
	}
	if existing == nil {
		return helpers.NewAppError(403, "Address not found.")
	}
	return s.repo.Delete(id)
}

// resolveLocation validates the city and its town, returning the canonical values.
func (s *AddressService) resolveLocation(cityInput, townInput string) (*deliveryCity, string, error) {
	city, err := s.cities.FindMatching(cityInput)
	if err != nil {
		return nil, "", err
	}
	if city == nil {
		return nil, "", helpers.NewAppError(403, "Invalid city name.")
	}

	hasTowns, err := s.cities.HasTowns(city.ID)
	if err != nil {
		return nil, "", err
	}
	if !hasTowns {
		// City without a configured town list accepts the free-text value.
		return &deliveryCity{Key: city.Key, ID: city.ID}, townInput, nil
	}

	town, err := s.cities.MatchTown(city.ID, townInput)
	if err != nil {
		return nil, "", err
	}
	if town == nil {
		return nil, "", helpers.NewAppError(403, "Invalid town name.")
	}
	return &deliveryCity{Key: city.Key, ID: city.ID}, town.Name, nil
}

func (s *AddressService) cityName(key string) string {
	city, err := s.cities.FindByKey(key)
	if err != nil || city == nil {
		return ""
	}
	return city.Name
}

// deliveryCity is a tiny internal view of a resolved city.
type deliveryCity struct {
	Key string
	ID  int64
}
