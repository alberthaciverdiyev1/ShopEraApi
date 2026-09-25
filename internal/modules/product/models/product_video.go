package models

import "time"

// ProductVideo maps the `product_videos` table (story controls included).
type ProductVideo struct {
	ID             int64      `gorm:"column:id;primaryKey"`
	ProductID      int64      `gorm:"column:product_id"`
	VideoPath      string     `gorm:"column:video_path"`
	IsStoryHidden  bool       `gorm:"column:is_story_hidden;default:false"`
	StoryExpiresAt *time.Time `gorm:"column:story_expires_at"`
	CreatedAt      time.Time  `gorm:"column:created_at"`
	UpdatedAt      time.Time  `gorm:"column:updated_at"`

	Product *Product `gorm:"foreignKey:ProductID"`
}

func (ProductVideo) TableName() string { return "product_videos" }
