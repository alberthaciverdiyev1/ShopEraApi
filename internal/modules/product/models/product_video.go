package models

import "time"

// ProductVideo maps the `product_videos` table.
type ProductVideo struct {
	ID        int64     `gorm:"column:id;primaryKey"`
	ProductID int64     `gorm:"column:product_id"`
	VideoPath string    `gorm:"column:video_path"`
	CreatedAt time.Time `gorm:"column:created_at"`
	UpdatedAt time.Time `gorm:"column:updated_at"`
}

func (ProductVideo) TableName() string { return "product_videos" }
