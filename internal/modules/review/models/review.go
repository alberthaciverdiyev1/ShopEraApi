// Package models holds the Review GORM model.
package models

import (
	"time"

	productmodels "shopera/internal/modules/product/models"
	usermodels "shopera/internal/modules/user/models"
)

// Review status values (Laravel ReviewStatus enum).
const (
	StatusPending  = "0"
	StatusApproved = "1"
	StatusRejected = "2"
)

// Review maps the `product_reviews` table.
type Review struct {
	ID        int64     `gorm:"column:id;primaryKey"`
	UserID    int64     `gorm:"column:user_id"`
	ProductID int64     `gorm:"column:product_id"`
	Rate      int       `gorm:"column:rate"`
	Comment   *string   `gorm:"column:comment"`
	Image     *string   `gorm:"column:image"`
	Status    string    `gorm:"column:status"`
	CreatedAt time.Time `gorm:"column:created_at"`
	UpdatedAt time.Time `gorm:"column:updated_at"`

	User    *usermodels.User       `gorm:"foreignKey:UserID"`
	Product *productmodels.Product `gorm:"foreignKey:ProductID"`
}

func (Review) TableName() string { return "product_reviews" }

// StatusValue maps a status name (PENDING/APPROVED/REJECTED) to its stored value.
func StatusValue(name string) (string, bool) {
	switch name {
	case "PENDING":
		return StatusPending, true
	case "APPROVED":
		return StatusApproved, true
	case "REJECTED":
		return StatusRejected, true
	default:
		return "", false
	}
}

// StatusName maps a stored value to its enum name.
func StatusName(value string) string {
	switch value {
	case StatusApproved:
		return "APPROVED"
	case StatusRejected:
		return "REJECTED"
	default:
		return "PENDING"
	}
}

// StatusLabel maps a stored value to a human label.
func StatusLabel(value string) string {
	switch value {
	case StatusApproved:
		return "Approved"
	case StatusRejected:
		return "Rejected"
	default:
		return "Pending"
	}
}
