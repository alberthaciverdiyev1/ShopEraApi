package services

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	"shopera/internal/modules/delivery/models"
	deliveryrepositories "shopera/internal/modules/delivery/repositories"
	deliveryrequests "shopera/internal/modules/delivery/requests"
	deliveryresponses "shopera/internal/modules/delivery/responses"
)

// DeliveryPriceService holds the delivery-price business logic.
type DeliveryPriceService struct {
	repo   *deliveryrepositories.DeliveryPriceRepository
	cities *deliveryrepositories.CityRepository
}

func NewDeliveryPriceService(repo *deliveryrepositories.DeliveryPriceRepository, cities *deliveryrepositories.CityRepository) *DeliveryPriceService {
	return &DeliveryPriceService{repo: repo, cities: cities}
}

// List returns delivery prices.
func (s *DeliveryPriceService) List(q helpers.Query, isAdmin bool) (gin.H, error) {
	items, total, err := s.repo.List(q, isAdmin)
	if err != nil {
		return nil, err
	}
	return gin.H{"data": deliveryresponses.DeliveryPriceCollection(items), "meta": q.Meta(total)}, nil
}

// Details returns the active delivery for a city name (nil when not found).
func (s *DeliveryPriceService) Details(cityName string) (*models.DeliveryPrice, error) {
	city, err := s.cities.FindMatching(cityName)
	if err != nil {
		return nil, err
	}
	if city == nil {
		return nil, nil
	}
	return s.repo.FindActiveByCityKey(city.Key)
}

// Cities returns the active cities as {id, key, name}.
func (s *DeliveryPriceService) Cities() ([]gin.H, error) {
	list, err := s.cities.List()
	if err != nil {
		return nil, err
	}
	out := make([]gin.H, 0, len(list))
	for _, c := range list {
		out = append(out, gin.H{"id": c.Key, "key": c.Key, "name": c.Name})
	}
	return out, nil
}

// Add creates (or restores+updates) a delivery price for a city.
func (s *DeliveryPriceService) Add(req deliveryrequests.DeliverySaveRequest) (*models.DeliveryPrice, string, error) {
	city, err := s.cities.FindActiveByKey(req.CityName)
	if err != nil {
		return nil, "", err
	}
	if city == nil {
		return nil, "", helpers.NewAppError(422, "The selected city name is invalid.")
	}

	existing, err := s.repo.FindByCityKeyIncludingTrashed(city.Key)
	if err != nil {
		return nil, "", err
	}
	fields := deliveryFields(req, city.Key)
	if existing != nil {
		if !existing.DeletedAt.Valid && existing.IsActive {
			return nil, "", helpers.NewAppError(400, "A delivery entry for this city already exists.")
		}
		restored, err := s.repo.RestoreUpdate(existing, fields)
		if err != nil {
			return nil, "", err
		}
		return restored, "Delivery entry was previously deleted, restored and updated with new values.", nil
	}

	created := &models.DeliveryPrice{IsActive: true}
	applyDeliveryFields(created, req, city.Key)
	if err := s.repo.Create(created); err != nil {
		return nil, "", err
	}
	return created, "Delivery added successfully.", nil
}

// Update changes a delivery price.
func (s *DeliveryPriceService) Update(id int64, req deliveryrequests.DeliverySaveRequest) (*models.DeliveryPrice, error) {
	if city, err := s.cities.FindActiveByKey(req.CityName); err != nil {
		return nil, err
	} else if city == nil {
		return nil, helpers.NewAppError(422, "The selected city name is invalid.")
	}
	return s.repo.Update(id, deliveryFields(req, req.CityName))
}

// Delete removes a delivery price.
func (s *DeliveryPriceService) Delete(id int64) error {
	d, err := s.repo.FindByID(id)
	if err != nil {
		return err
	}
	if d == nil {
		return helpers.NewAppError(404, "Delivery not found.")
	}
	return s.repo.Delete(id)
}

func deliveryFields(req deliveryrequests.DeliverySaveRequest, cityKey string) map[string]any {
	fields := map[string]any{
		"city_name":  cityKey,
		"price":      req.Price,
		"fast_price": req.FastPrice,
	}
	if req.FreeFrom != nil {
		fields["free_from"] = req.FreeFrom
	}
	if req.DeliveryTime != nil {
		fields["delivery_time"] = *req.DeliveryTime
	}
	if req.FastDeliveryTime != nil {
		fields["fast_delivery_time"] = *req.FastDeliveryTime
	}
	if req.IsActive != nil {
		fields["is_active"] = *req.IsActive
	}
	return fields
}

func applyDeliveryFields(model *models.DeliveryPrice, req deliveryrequests.DeliverySaveRequest, cityKey string) {
	model.CityName = cityKey
	model.Price = req.Price
	model.FastPrice = req.FastPrice
	model.FreeFrom = req.FreeFrom
	model.DeliveryTime = req.DeliveryTime
	model.FastDeliveryTime = req.FastDeliveryTime
	if req.IsActive != nil {
		model.IsActive = *req.IsActive
	}
}
