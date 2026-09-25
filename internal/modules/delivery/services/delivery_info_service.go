package services

import (
	"strings"

	"shopera/internal/helpers"
	"shopera/internal/modules/delivery/models"
	deliveryrepositories "shopera/internal/modules/delivery/repositories"
	deliveryrequests "shopera/internal/modules/delivery/requests"
)

// DeliveryInfoService holds the delivery-info business logic.
type DeliveryInfoService struct {
	repo *deliveryrepositories.DeliveryInfoRepository
}

func NewDeliveryInfoService(repo *deliveryrepositories.DeliveryInfoRepository) *DeliveryInfoService {
	return &DeliveryInfoService{repo: repo}
}

// List returns delivery info rows, optionally filtered by a UI type.
func (s *DeliveryInfoService) List(typeFilter string) ([]models.DeliveryInfo, error) {
	var types []string
	switch typeFilter {
	case "delivery":
		types = []string{"STANDARD", "STANDARD_FAST"}
	case "pickup":
		types = []string{"PICKUP_POINT", "TAKE_FROM_STORE"}
	}
	return s.repo.List(types)
}

// ByType returns a delivery info row by type.
func (s *DeliveryInfoService) ByType(deliveryType string) (*models.DeliveryInfo, error) {
	return s.repo.FindByType(strings.ToUpper(deliveryType))
}

// Update changes a delivery info row.
func (s *DeliveryInfoService) Update(id int64, req deliveryrequests.DeliveryInfoSaveRequest) (*models.DeliveryInfo, error) {
	fields := map[string]any{}
	if req.Type != "" {
		fields["type"] = req.Type
	}
	if req.Description != nil {
		fields["description"] = req.Description
	}
	d, err := s.repo.Update(id, fields)
	if err != nil {
		return nil, helpers.NewAppError(404, "Delivery info not found.")
	}
	return d, nil
}
