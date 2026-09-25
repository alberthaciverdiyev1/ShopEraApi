package models

import (
	"time"

	"gorm.io/gorm"
)

// OtpEmail maps the `otp_emails` table. Despite the name it stores the OTP keyed
// by the normalized phone (or an e-mail address) — see OtpService.
type OtpEmail struct {
	ID           int64          `gorm:"column:id;primaryKey"`
	Email        string         `gorm:"column:email"`
	DeactiveDate time.Time      `gorm:"column:deactive_date"`
	OtpCode      int16          `gorm:"column:otp_code"`
	CreatedAt    time.Time      `gorm:"column:created_at"`
	UpdatedAt    time.Time      `gorm:"column:updated_at"`
	DeletedAt    gorm.DeletedAt `gorm:"column:deleted_at;index"`
}

func (OtpEmail) TableName() string { return "otp_emails" }
