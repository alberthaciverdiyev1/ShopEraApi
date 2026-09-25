// Package models holds the Product module GORM models.
package models

import (
	"time"

	"gorm.io/gorm"

	brandmodels "shopera/internal/modules/brand/models"
	categorymodels "shopera/internal/modules/category/models"
	colormodels "shopera/internal/modules/color/models"
	sizemodels "shopera/internal/modules/size/models"
)

// Product maps the existing `products` table (no Store/marketplace columns).
type Product struct {
	ID                 int64             `gorm:"column:id;primaryKey"`
	BrandID            *int64            `gorm:"column:brand_id"`
	CategoryID         *int64            `gorm:"column:category_id"`
	Title              map[string]string `gorm:"column:title;serializer:json"`
	Description        map[string]string `gorm:"column:description;serializer:json"`
	Gender             *string           `gorm:"column:gender"`
	Sku                *string           `gorm:"column:sku"`
	Price              *float64          `gorm:"column:price"`
	IsActive           bool              `gorm:"column:is_active;default:true"`
	Discount           *float64          `gorm:"column:discount"`
	StockCount         int               `gorm:"column:stock_count"`
	Views              int               `gorm:"column:views"`
	SalesCount         int               `gorm:"column:sales_count"`
	UserID             *int64            `gorm:"column:user_id"`
	IsSuggest          bool              `gorm:"column:is_suggest"`
	IsPinned           bool              `gorm:"column:is_pinned"`
	DiscountExpireDate *time.Time        `gorm:"column:discount_expire_date"`
	PurchaseLimit      *int              `gorm:"column:purchase_limit"`
	WholesalePrice     *float64          `gorm:"column:wholesale_price"`
	Weight             *float64          `gorm:"column:weight"`
	CreatedAt          time.Time         `gorm:"column:created_at"`
	UpdatedAt          time.Time         `gorm:"column:updated_at"`
	DeletedAt          gorm.DeletedAt    `gorm:"column:deleted_at;index"`

	Colors   []colormodels.Color      `gorm:"many2many:color_product"`
	Sizes    []sizemodels.Size        `gorm:"many2many:product_size"`
	Images   []ProductImage           `gorm:"foreignKey:ProductID"`
	Videos   []ProductVideo           `gorm:"foreignKey:ProductID"`
	Category *categorymodels.Category `gorm:"foreignKey:CategoryID"`
	Brand    *brandmodels.Brand       `gorm:"foreignKey:BrandID"`
}

func (Product) TableName() string { return "products" }
