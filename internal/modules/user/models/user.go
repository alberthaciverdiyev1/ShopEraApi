// Package models holds the User GORM model.
package models

import (
	"time"

	"gorm.io/gorm"
)

// User maps the existing `users` table.
type User struct {
	ID              int64          `gorm:"column:id;primaryKey"`
	Name            *string        `gorm:"column:name"`
	Surname         *string        `gorm:"column:surname"`
	Phone           string         `gorm:"column:phone"`
	Email           *string        `gorm:"column:email"`
	EmailVerifiedAt *time.Time     `gorm:"column:email_verified_at"`
	Password        string         `gorm:"column:password"`
	IsActive        bool           `gorm:"column:is_active;default:true"`
	IsWholesaler    bool           `gorm:"column:is_wholesaler;default:false"`
	RememberToken   *string        `gorm:"column:remember_token"`
	CreatedAt       time.Time      `gorm:"column:created_at"`
	UpdatedAt       time.Time      `gorm:"column:updated_at"`
	DeletedAt       gorm.DeletedAt `gorm:"column:deleted_at;index"`
}

func (User) TableName() string { return "users" }
