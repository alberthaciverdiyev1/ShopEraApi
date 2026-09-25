// Package services holds PromoCode module business logic.
package services

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	addressmodels "shopera/internal/modules/address/models"
	addressrepositories "shopera/internal/modules/address/repositories"
	basketrepositories "shopera/internal/modules/basket/repositories"
	deliverymodels "shopera/internal/modules/delivery/models"
	deliveryrepositories "shopera/internal/modules/delivery/repositories"
	producthelpers "shopera/internal/modules/product/helpers"
	productrepositories "shopera/internal/modules/product/repositories"
	"shopera/internal/modules/promocode/models"
	promocoderepositories "shopera/internal/modules/promocode/repositories"
	promocoderequests "shopera/internal/modules/promocode/requests"
	promocoderesponses "shopera/internal/modules/promocode/responses"
)

// PromoCodeService holds the promo-code business logic.
type PromoCodeService struct {
	repo      *promocoderepositories.PromoCodeRepository
	addresses *addressrepositories.AddressRepository
	cities    *deliveryrepositories.CityRepository
	delivery  *deliveryrepositories.DeliveryPriceRepository
	baskets   *basketrepositories.BasketRepository
	products  *productrepositories.ProductRepository
}

func NewPromoCodeService(
	repo *promocoderepositories.PromoCodeRepository,
	addresses *addressrepositories.AddressRepository,
	cities *deliveryrepositories.CityRepository,
	delivery *deliveryrepositories.DeliveryPriceRepository,
	baskets *basketrepositories.BasketRepository,
	products *productrepositories.ProductRepository,
) *PromoCodeService {
	return &PromoCodeService{repo: repo, addresses: addresses, cities: cities, delivery: delivery, baskets: baskets, products: products}
}

// List returns promo codes.
func (s *PromoCodeService) List(q helpers.Query, isActive *bool) ([]gin.H, error) {
	items, err := s.repo.List(q, isActive)
	if err != nil {
		return nil, err
	}
	return promocoderesponses.Collection(items), nil
}

// Details returns a promo code.
func (s *PromoCodeService) Details(id int64) (*models.PromoCode, error) {
	return s.repo.FindByID(id)
}

// Add creates a promo code.
func (s *PromoCodeService) Add(req promocoderequests.SaveRequest) (*models.PromoCode, error) {
	taken, err := s.repo.ExistsByCode(req.Code, 0)
	if err != nil {
		return nil, err
	}
	if taken {
		return nil, helpers.NewAppError(422, "The code has already been taken.")
	}
	promo := &models.PromoCode{Code: req.Code, DiscountPercent: req.DiscountPercent, IsActive: true}
	if req.UserCount != nil {
		promo.UserCount = *req.UserCount
	}
	if req.IsActive != nil {
		promo.IsActive = *req.IsActive
	}
	if err := s.repo.Create(promo); err != nil {
		return nil, err
	}
	return promo, nil
}

// Update changes a promo code.
func (s *PromoCodeService) Update(id int64, req promocoderequests.SaveRequest) (*models.PromoCode, error) {
	existing, err := s.repo.FindByID(id)
	if err != nil {
		return nil, err
	}
	if existing == nil {
		return nil, helpers.NewAppError(403, "Promo Code not found.")
	}
	if taken, err := s.repo.ExistsByCode(req.Code, id); err != nil {
		return nil, err
	} else if taken {
		return nil, helpers.NewAppError(422, "The code has already been taken.")
	}

	fields := map[string]any{"code": req.Code, "discount_percent": req.DiscountPercent}
	if req.UserCount != nil {
		fields["user_count"] = *req.UserCount
	}
	if req.IsActive != nil {
		fields["is_active"] = *req.IsActive
	}
	return s.repo.Update(id, fields)
}

// Delete removes a promo code.
func (s *PromoCodeService) Delete(id int64) error {
	existing, err := s.repo.FindByID(id)
	if err != nil {
		return err
	}
	if existing == nil {
		return helpers.NewAppError(403, "Promo Code not found.")
	}
	return s.repo.Delete(id)
}

// Check validates a promo code and returns the discounted totals for the basket.
func (s *PromoCodeService) Check(userID int64, code string, addressID *int64) (gin.H, error) {
	address, err := s.resolveAddress(userID, addressID)
	if err != nil {
		return nil, err
	}
	if address == nil {
		return nil, helpers.NewAppError(403, "Please set a valid default address with a city before placing an order.")
	}

	delivery, err := s.delivery.FindActiveByCityKey(address.City)
	if err != nil {
		return nil, err
	}
	if delivery == nil {
		return nil, helpers.NewAppError(403, "Delivery service is not available for your city.")
	}

	promo, err := s.repo.FindActiveByCode(code)
	if err != nil {
		return nil, err
	}
	if promo == nil {
		return nil, helpers.NewAppError(404, "Promo Code not found.")
	}
	if promo.UserCount <= 0 {
		return nil, helpers.NewAppError(403, "Promo Code usage limit reached.")
	}
	if used, err := s.repo.UsedByUser(promo.ID, userID); err != nil {
		return nil, err
	} else if used {
		return nil, helpers.NewAppError(403, "You have already used this Promo Code.")
	}

	total, err := s.basketTotal(userID)
	if err != nil {
		return nil, err
	}
	if total <= 0 {
		return nil, helpers.NewAppError(403, "Your basket is empty.")
	}

	discountPercent := 0.0
	if promo.DiscountPercent != nil {
		discountPercent = *promo.DiscountPercent
	}
	discounted := round2(total * (1 - discountPercent/100))
	shipping := s.shipping(delivery, total)

	out := promocoderesponses.JSON(*promo)
	out["original_price"] = round2(total + shipping)
	out["discounted_price"] = round2(discounted + shipping)
	return out, nil
}

// basketTotal sums the retail final price of the user's selected basket items.
func (s *PromoCodeService) basketTotal(userID int64) (float64, error) {
	items, err := s.baskets.List(userID, helpers.Query{PerPage: 500})
	if err != nil {
		return 0, err
	}
	total := 0.0
	for _, b := range items {
		if !b.Selected || b.IsOrdered || b.Product == nil {
			continue
		}
		pivots, err := s.products.SizePivots([]int64{b.ProductID})
		if err != nil {
			return 0, err
		}
		unit := producthelpers.Retail(*b.Product, pivots[b.ProductID], b.SizeID).Final
		total += unit * float64(b.Quantity)
	}
	return total, nil
}

func (s *PromoCodeService) shipping(delivery *deliverymodels.DeliveryPrice, total float64) float64 {
	freeFrom := 0.0
	if delivery.FreeFrom != nil {
		freeFrom = *delivery.FreeFrom
	}
	if total < freeFrom {
		if delivery.Price != nil {
			return *delivery.Price
		}
	}
	return 0
}

func (s *PromoCodeService) resolveAddress(userID int64, addressID *int64) (*addressmodels.Address, error) {
	if addressID != nil {
		return s.addresses.FindOwned(userID, *addressID)
	}
	return s.addresses.FindDefault(userID)
}

func round2(v float64) float64 { return float64(int64(v*100+0.5)) / 100 }
