// Package migrate owns the database schema: a one-time schema generator from the
// entities, plus the versioned SQL migration runner used for tenant provisioning.
package migrate

import "time"

// Additional (migration-only) models for tables the app queries without a
// dedicated entity.

type PersonalAccessToken struct {
	ID            int64      `gorm:"column:id;primaryKey"`
	TokenableType string     `gorm:"column:tokenable_type"`
	TokenableID   int64      `gorm:"column:tokenable_id"`
	Name          string     `gorm:"column:name"`
	Token         string     `gorm:"column:token;size:64"`
	Abilities     *string    `gorm:"column:abilities"`
	LastUsedAt    *time.Time `gorm:"column:last_used_at"`
	ExpiresAt     *time.Time `gorm:"column:expires_at"`
	CreatedAt     *time.Time `gorm:"column:created_at"`
	UpdatedAt     *time.Time `gorm:"column:updated_at"`
}

func (PersonalAccessToken) TableName() string { return "personal_access_tokens" }

type NotificationUser struct {
	ID             int64     `gorm:"column:id;primaryKey"`
	NotificationID int64     `gorm:"column:notification_id"`
	UserID         int64     `gorm:"column:user_id"`
	CreatedAt      time.Time `gorm:"column:created_at"`
	UpdatedAt      time.Time `gorm:"column:updated_at"`
}

func (NotificationUser) TableName() string { return "notification_user" }

type ProductStockSubscription struct {
	ID         int64      `gorm:"column:id;primaryKey"`
	ProductID  int64      `gorm:"column:product_id"`
	UserID     int64      `gorm:"column:user_id"`
	NotifiedAt *time.Time `gorm:"column:notified_at"`
	CreatedAt  time.Time  `gorm:"column:created_at"`
	UpdatedAt  time.Time  `gorm:"column:updated_at"`
}

func (ProductStockSubscription) TableName() string { return "product_stock_subscriptions" }
