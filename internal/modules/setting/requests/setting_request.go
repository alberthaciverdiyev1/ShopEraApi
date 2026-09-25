// Package requests holds Setting module request payloads.
package requests

// UpdateRequest carries the writable settings fields (all optional).
type UpdateRequest struct {
	InstagramURL            *string  `json:"instagram_url"`
	TikTokURL               *string  `json:"tiktok_url"`
	WhatsAppNumber          *string  `json:"whatsapp_number"`
	PhoneNumber1            *string  `json:"phone_number_1"`
	PhoneNumber2            *string  `json:"phone_number_2"`
	PhoneNumber3            *string  `json:"phone_number_3"`
	PhoneNumber4            *string  `json:"phone_number_4"`
	GoogleMapURL            *string  `json:"google_map_url"`
	Address                 *string  `json:"address"`
	AppVersion              *string  `json:"app_version" binding:"omitempty,regexp=^\\d+(\\.\\d+){1,2}$"`
	AppVersionIOS           *string  `json:"app_version_ios" binding:"omitempty,regexp=^\\d+(\\.\\d+){1,2}$"`
	MinimalPurchasePrice    *float64 `json:"minimal_purchase_price"`
	PublicLowStockThreshold *int     `json:"public_low_stock_threshold" binding:"omitempty,gte=0"`
}

// ChangeLocaleRequest is the change-locale payload.
type ChangeLocaleRequest struct {
	Locale string `json:"locale" binding:"required,oneof=az en ru tr"`
}
