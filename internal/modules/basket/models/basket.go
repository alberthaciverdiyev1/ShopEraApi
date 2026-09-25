// Package models holds the Basket GORM model.
package models

import (
	"time"

	"gorm.io/gorm"

	productmodels "shopera/internal/modules/product/models"
)

// Basket maps the `baskets` table.
type Basket struct {
	ID            int64          `gorm:"column:id;primaryKey"`
	UserID        int64          `gorm:"column:user_id"`
	ProductID     int64          `gorm:"column:product_id"`
	Quantity      int            `gorm:"column:quantity"`
	ColorID       *int64         `gorm:"column:color_id"`
	SizeID        *int64         `gorm:"column:size_id"`
	Gender        *string        `gorm:"column:gender"`
	Selected      bool           `gorm:"column:selected"`
	IsOrdered     bool           `gorm:"column:is_ordered"`
	TransactionID *string        `gorm:"column:transaction_id"`
	CreatedAt     time.Time      `gorm:"column:created_at"`
	UpdatedAt     time.Time      `gorm:"column:updated_at"`
	DeletedAt     gorm.DeletedAt `gorm:"column:deleted_at;index"`

	Product *productmodels.Product `gorm:"foreignKey:ProductID"`
}

func (Basket) TableName() string { return "baskets" }
