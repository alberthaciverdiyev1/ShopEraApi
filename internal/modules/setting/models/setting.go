// Package models holds the Setting GORM model.
package models

import (
	"time"

	"gorm.io/gorm"
)

// Setting maps the single-row `settings` table. Translatable jsonb fields are maps.
type Setting struct {
	ID                            int64             `gorm:"column:id;primaryKey" json:"id"`
	InstagramURL                  *string           `gorm:"column:instagram_url" json:"instagram_url"`
	TikTokURL                     *string           `gorm:"column:tiktok_url" json:"tiktok_url"`
	WhatsAppNumber                *string           `gorm:"column:whatsapp_number" json:"whatsapp_number"`
	PhoneNumber1                  *string           `gorm:"column:phone_number_1" json:"phone_number_1"`
	PhoneNumber2                  *string           `gorm:"column:phone_number_2" json:"phone_number_2"`
	PhoneNumber3                  *string           `gorm:"column:phone_number_3" json:"phone_number_3"`
	PhoneNumber4                  *string           `gorm:"column:phone_number_4" json:"phone_number_4"`
	GoogleMapURL                  *string           `gorm:"column:google_map_url" json:"google_map_url"`
	Address                       *string           `gorm:"column:address" json:"address"`
	ReferralRewardAmount          *float64          `gorm:"column:referral_reward_amount" json:"referral_reward_amount"`
	AppVersion                    *string           `gorm:"column:app_version" json:"app_version"`
	AppVersionIOS                 *string           `gorm:"column:app_version_ios" json:"app_version_ios"`
	MinimalPurchasePrice          *float64          `gorm:"column:minimal_purchase_price" json:"minimal_purchase_price"`
	WholesaleMinimalPurchasePrice *float64          `gorm:"column:wholesale_minimal_purchase_price" json:"wholesale_minimal_purchase_price"`
	StoreCommissionPercent        *float64          `gorm:"column:store_commission_percent" json:"store_commission_percent"`
	StoreNegativeBalanceLimit     *float64          `gorm:"column:store_negative_balance_limit" json:"store_negative_balance_limit"`
	StoreHandoverHours            *int              `gorm:"column:store_handover_hours" json:"store_handover_hours"`
	StoreLatePenaltyAmount        *float64          `gorm:"column:store_late_penalty_amount" json:"store_late_penalty_amount"`
	StoreMinWithdrawalAmount      *float64          `gorm:"column:store_min_withdrawal_amount" json:"store_min_withdrawal_amount"`
	StoreReleaseHoldHours         *int              `gorm:"column:store_release_hold_hours" json:"store_release_hold_hours"`
	SellerInstructions            map[string]string `gorm:"column:seller_instructions;serializer:json" json:"seller_instructions"`
	PublicLowStockThreshold       *int              `gorm:"column:public_low_stock_threshold" json:"public_low_stock_threshold"`
	MarketplaceEnabled            *bool             `gorm:"column:marketplace_enabled" json:"marketplace_enabled"`
	CreatedAt                     time.Time         `gorm:"column:created_at" json:"created_at"`
	UpdatedAt                     time.Time         `gorm:"column:updated_at" json:"updated_at"`
	DeletedAt                     gorm.DeletedAt    `gorm:"column:deleted_at" json:"-"`
}

func (Setting) TableName() string { return "settings" }
