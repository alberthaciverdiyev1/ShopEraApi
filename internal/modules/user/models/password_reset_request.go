package models

import "time"

// Password reset request statuses.
const (
	ResetStatusPending   = "pending"
	ResetStatusResolved  = "resolved"
	ResetStatusDismissed = "dismissed"
)

// PasswordResetRequest maps the `password_reset_requests` table.
type PasswordResetRequest struct {
	ID         int64      `gorm:"column:id;primaryKey"`
	UserID     int64      `gorm:"column:user_id"`
	Phone      string     `gorm:"column:phone"`
	Status     string     `gorm:"column:status"`
	Note       *string    `gorm:"column:note"`
	ResolvedBy *int64     `gorm:"column:resolved_by"`
	ResolvedAt *time.Time `gorm:"column:resolved_at"`
	CreatedAt  time.Time  `gorm:"column:created_at"`
	UpdatedAt  time.Time  `gorm:"column:updated_at"`
}

func (PasswordResetRequest) TableName() string { return "password_reset_requests" }
