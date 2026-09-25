package services

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	deliveryhelpers "shopera/internal/modules/delivery/helpers"
	"shopera/internal/modules/delivery/models"
	deliveryrepositories "shopera/internal/modules/delivery/repositories"
	deliveryrequests "shopera/internal/modules/delivery/requests"
	deliveryresponses "shopera/internal/modules/delivery/responses"
)

// PickupPointService holds the pickup-point business logic.
type PickupPointService struct {
	repo *deliveryrepositories.PickupPointRepository
}

func NewPickupPointService(repo *deliveryrepositories.PickupPointRepository) *PickupPointService {
	return &PickupPointService{repo: repo}
}

// List returns pickup points.
func (s *PickupPointService) List(q helpers.Query, isActive *bool, isAdmin bool) (gin.H, error) {
	items, total, err := s.repo.List(q, isActive, isAdmin)
	if err != nil {
		return nil, err
	}
	if q.All {
		return gin.H{"data": deliveryresponses.PickupPointCollection(items)}, nil
	}
	return gin.H{"data": deliveryresponses.PickupPointCollection(items), "meta": q.Meta(total)}, nil
}

// Details returns an active pickup point.
func (s *PickupPointService) Details(id int64) (*models.PickupPoint, error) {
	p, err := s.repo.FindByID(id, true)
	if err != nil {
		return nil, err
	}
	if p == nil {
		return nil, helpers.NewAppError(404, "Pickup point not found.")
	}
	return p, nil
}

// Add creates (or restores+updates) a pickup point by name.
func (s *PickupPointService) Add(req deliveryrequests.PickupPointSaveRequest) (*models.PickupPoint, error) {
	existing, err := s.repo.FindByNameIncludingTrashed(req.Name)
	if err != nil {
		return nil, err
	}
	if existing != nil {
		return s.repo.RestoreUpdate(existing, pickupFields(req))
	}
	p := &models.PickupPoint{IsActive: true}
	applyPickupFields(p, req)
	if err := s.repo.Create(p); err != nil {
		return nil, err
	}
	return p, nil
}

// Update changes a pickup point.
func (s *PickupPointService) Update(id int64, req deliveryrequests.PickupPointSaveRequest) (*models.PickupPoint, error) {
	existing, err := s.repo.FindByID(id, false)
	if err != nil {
		return nil, err
	}
	if existing == nil {
		return nil, helpers.NewAppError(404, "Pickup point not found.")
	}
	if taken, err := s.repo.ExistsByName(req.Name, id); err != nil {
		return nil, err
	} else if taken {
		return nil, helpers.NewAppError(422, "The name has already been taken.")
	}
	return s.repo.Update(id, pickupFields(req))
}

// DetailsAdmin returns a pickup point regardless of active state.
func (s *PickupPointService) DetailsAdmin(id int64) (*models.PickupPoint, error) {
	p, err := s.repo.FindByID(id, false)
	if err != nil {
		return nil, err
	}
	if p == nil {
		return nil, helpers.NewAppError(404, "Pickup point not found.")
	}
	return p, nil
}

// Delete removes a pickup point.
func (s *PickupPointService) Delete(id int64) error {
	p, err := s.repo.FindByID(id, false)
	if err != nil {
		return err
	}
	if p == nil {
		return helpers.NewAppError(404, "Pickup point not found.")
	}
	return s.repo.Delete(id)
}

func pickupFields(req deliveryrequests.PickupPointSaveRequest) map[string]any {
	fields := map[string]any{
		"name":          req.Name,
		"address":       req.Address,
		"price":         req.Price,
		"delivery_time": deliveryhelpers.FillLower(req.DeliveryTime),
	}
	if req.IsActive != nil {
		fields["is_active"] = *req.IsActive
	}
	return fields
}

func applyPickupFields(p *models.PickupPoint, req deliveryrequests.PickupPointSaveRequest) {
	p.Name = req.Name
	p.Address = req.Address
	p.Price = req.Price
	p.DeliveryTime = deliveryhelpers.FillLower(req.DeliveryTime)
	if req.IsActive != nil {
		p.IsActive = *req.IsActive
	}
}
