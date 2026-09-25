package models

import (
	"time"

	"gorm.io/gorm"

	addressmodels "shopera/internal/modules/address/models"
	usermodels "shopera/internal/modules/user/models"
)

// Order maps the `orders` table (Starex columns excluded).
type Order struct {
	ID            int64          `gorm:"column:id;primaryKey"`
	UserID        int64          `gorm:"column:user_id"`
	AddressID     *int64         `gorm:"column:address_id"`
	TransactionID string         `gorm:"column:transaction_id"`
	TotalPrice    *float64       `gorm:"column:total_price"`
	DiscountPrice *float64       `gorm:"column:discount_price"`
	ShippingPrice *float64       `gorm:"column:shipping_price"`
	PaidAt        *time.Time     `gorm:"column:paid_at"`
	Note          *string        `gorm:"column:note"`
	PromoCode     *string        `gorm:"column:promo_code"`
	AddressType   *string        `gorm:"column:address_type"`
	AddressTypeID *int64         `gorm:"column:address_type_id"`
	PaymentType   *string        `gorm:"column:payment_type"`
	PricingType   *string        `gorm:"column:pricing_type"`
	CreatedAt     time.Time      `gorm:"column:created_at"`
	UpdatedAt     time.Time      `gorm:"column:updated_at"`
	DeletedAt     gorm.DeletedAt `gorm:"column:deleted_at;index"`

	Items    []OrderItem            `gorm:"foreignKey:OrderID"`
	Statuses []OrderStatusRow       `gorm:"foreignKey:OrderID"`
	Address  *addressmodels.Address `gorm:"foreignKey:AddressID"`
	User     *usermodels.User       `gorm:"foreignKey:UserID"`
}

func (Order) TableName() string { return "orders" }
