// Package services holds Setting module business logic.
package services

import (
	"shopera/internal/helpers"
	settingmodels "shopera/internal/modules/setting/models"
	settingrepositories "shopera/internal/modules/setting/repositories"
	settingrequests "shopera/internal/modules/setting/requests"
)

// SettingService holds the settings business logic and the getters other modules use.
type SettingService struct {
	repo *settingrepositories.SettingRepository
}

func NewSettingService(repo *settingrepositories.SettingRepository) *SettingService {
	return &SettingService{repo: repo}
}

// List returns all settings rows.
func (s *SettingService) List() ([]settingmodels.Setting, error) {
	return s.repo.All()
}

// Update applies changes to the first settings row.
func (s *SettingService) Update(req settingrequests.UpdateRequest) (*settingmodels.Setting, error) {
	fields := map[string]any{}
	set := func(key string, value any) {
		if value != nil {
			fields[key] = value
		}
	}
	set("instagram_url", req.InstagramURL)
	set("tiktok_url", req.TikTokURL)
	set("whatsapp_number", req.WhatsAppNumber)
	set("phone_number_1", req.PhoneNumber1)
	set("phone_number_2", req.PhoneNumber2)
	set("phone_number_3", req.PhoneNumber3)
	set("phone_number_4", req.PhoneNumber4)
	set("google_map_url", req.GoogleMapURL)
	set("address", req.Address)
	set("app_version", req.AppVersion)
	set("app_version_ios", req.AppVersionIOS)
	set("minimal_purchase_price", req.MinimalPurchasePrice)
	set("wholesale_minimal_purchase_price", req.WholesaleMinimalPurchasePrice)
	set("store_commission_percent", req.StoreCommissionPercent)
	set("store_negative_balance_limit", req.StoreNegativeBalanceLimit)
	set("store_handover_hours", req.StoreHandoverHours)
	set("store_late_penalty_amount", req.StoreLatePenaltyAmount)
	set("public_low_stock_threshold", req.PublicLowStockThreshold)
	if req.SellerInstructions != nil {
		fields["seller_instructions"] = req.SellerInstructions
	}

	return s.repo.UpdateFirst(fields)
}

// IsMarketplaceEnabled reports whether the marketplace is on (default true).
func (s *SettingService) IsMarketplaceEnabled() bool {
	first, err := s.repo.First()
	if err != nil || first == nil || first.MarketplaceEnabled == nil {
		return true
	}
	return *first.MarketplaceEnabled
}

// PublicLowStockThreshold returns the public low-stock threshold (default 20).
func (s *SettingService) PublicLowStockThreshold() int {
	first, err := s.repo.First()
	if err != nil || first == nil || first.PublicLowStockThreshold == nil {
		return 20
	}
	return *first.PublicLowStockThreshold
}

// MinimalPurchasePrice returns the minimum purchase price (default 15).
func (s *SettingService) MinimalPurchasePrice() float64 {
	first, err := s.repo.First()
	if err != nil || first == nil || first.MinimalPurchasePrice == nil {
		return 15
	}
	return *first.MinimalPurchasePrice
}

// WholesaleMinimalPurchasePrice returns the wholesale minimum (default 100).
func (s *SettingService) WholesaleMinimalPurchasePrice() float64 {
	first, err := s.repo.First()
	if err != nil || first == nil || first.WholesaleMinimalPurchasePrice == nil {
		return 100
	}
	return *first.WholesaleMinimalPurchasePrice
}

// ChangeLocale validates the locale and echoes it back.
func (s *SettingService) ChangeLocale(locale string) (string, error) {
	switch locale {
	case "az", "en", "ru", "tr":
		return locale, nil
	default:
		return "", helpers.NewAppError(422, "The selected locale is invalid.")
	}
}
