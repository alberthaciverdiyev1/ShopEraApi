// Package models holds the Balance GORM model.
package models

import (
	"time"

	"gorm.io/gorm"
)

// Balance types (Laravel BalanceType enum).
const (
	TypeDeposit    = "deposit"
	TypeWithdrawal = "withdrawal"
	TypeRefund     = "refund"
	TypeBonus      = "bonus"
	TypeWaiting    = "waiting"
	TypeReferral   = "referral"
)

// Balance maps the `balances` table.
type Balance struct {
	ID                int64          `gorm:"column:id;primaryKey"`
	UserID            int64          `gorm:"column:user_id"`
	Amount            *float64       `gorm:"column:amount"`
	Type              string         `gorm:"column:type"`
	TransactionEPoint *string        `gorm:"column:transaction_e_point"`
	TransactionOrder  *string        `gorm:"column:transaction_order"`
	Note              *string        `gorm:"column:note"`
	CreatedAt         time.Time      `gorm:"column:created_at"`
	UpdatedAt         time.Time      `gorm:"column:updated_at"`
	DeletedAt         gorm.DeletedAt `gorm:"column:deleted_at;index"`
}

func (Balance) TableName() string { return "balances" }
