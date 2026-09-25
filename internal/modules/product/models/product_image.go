package models

import (
	"time"

	"gorm.io/gorm"
)

// ProductImage maps the `product_images` table.
type ProductImage struct {
	ID        int64          `gorm:"column:id;primaryKey"`
	ProductID int64          `gorm:"column:product_id"`
	ImagePath string         `gorm:"column:image_path"`
	ColorID   *int64         `gorm:"column:color_id"`
	CreatedAt time.Time      `gorm:"column:created_at"`
	UpdatedAt time.Time      `gorm:"column:updated_at"`
	DeletedAt gorm.DeletedAt `gorm:"column:deleted_at;index"`
}

func (ProductImage) TableName() string { return "product_images" }
