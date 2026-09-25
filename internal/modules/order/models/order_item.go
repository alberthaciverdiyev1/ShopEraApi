package models

import (
	"time"

	"gorm.io/gorm"

	colormodels "shopera/internal/modules/color/models"
	productmodels "shopera/internal/modules/product/models"
	sizemodels "shopera/internal/modules/size/models"
)

// OrderItem maps the `order_items` table.
type OrderItem struct {
	ID         int64          `gorm:"column:id;primaryKey"`
	OrderID    int64          `gorm:"column:order_id"`
	ProductID  int64          `gorm:"column:product_id"`
	ColorID    *int64         `gorm:"column:color_id"`
	SizeID     *int64         `gorm:"column:size_id"`
	Quantity   int            `gorm:"column:quantity"`
	UnitPrice  *float64       `gorm:"column:unit_price"`
	TotalPrice *float64       `gorm:"column:total_price"`
	CreatedAt  time.Time      `gorm:"column:created_at"`
	UpdatedAt  time.Time      `gorm:"column:updated_at"`
	DeletedAt  gorm.DeletedAt `gorm:"column:deleted_at;index"`

	Product *productmodels.Product `gorm:"foreignKey:ProductID"`
	Color   *colormodels.Color     `gorm:"foreignKey:ColorID"`
	Size    *sizemodels.Size       `gorm:"foreignKey:SizeID"`
}

func (OrderItem) TableName() string { return "order_items" }
