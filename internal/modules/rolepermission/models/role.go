// Package models holds the RoleAndPermissions GORM models (spatie tables).
package models

import "time"

// Guard is the auth guard used across the app.
const Guard = "sanctum"

// UserMorph is the morph type stored for user model relations (spatie tables).
const UserMorph = `Modules\User\Http\Entities\User`

// Role maps the `roles` table.
type Role struct {
	ID        int64     `gorm:"column:id;primaryKey" json:"id"`
	Name      string    `gorm:"column:name" json:"name"`
	GuardName string    `gorm:"column:guard_name" json:"guard_name"`
	CreatedAt time.Time `gorm:"column:created_at" json:"created_at"`
	UpdatedAt time.Time `gorm:"column:updated_at" json:"updated_at"`
}

func (Role) TableName() string { return "roles" }

// Permission maps the `permissions` table.
type Permission struct {
	ID        int64     `gorm:"column:id;primaryKey" json:"id"`
	Name      string    `gorm:"column:name" json:"name"`
	GuardName string    `gorm:"column:guard_name" json:"guard_name"`
	CreatedAt time.Time `gorm:"column:created_at" json:"created_at"`
	UpdatedAt time.Time `gorm:"column:updated_at" json:"updated_at"`
}

func (Permission) TableName() string { return "permissions" }

// ModelHasRole is the role pivot.
type ModelHasRole struct {
	RoleID    int64  `gorm:"column:role_id;primaryKey"`
	ModelType string `gorm:"column:model_type;primaryKey"`
	ModelID   int64  `gorm:"column:model_id;primaryKey"`
}

func (ModelHasRole) TableName() string { return "model_has_roles" }

// ModelHasPermission is the direct permission pivot.
type ModelHasPermission struct {
	PermissionID int64  `gorm:"column:permission_id;primaryKey"`
	ModelType    string `gorm:"column:model_type;primaryKey"`
	ModelID      int64  `gorm:"column:model_id;primaryKey"`
}

func (ModelHasPermission) TableName() string { return "model_has_permissions" }

// RoleHasPermission is the role-permission pivot.
type RoleHasPermission struct {
	PermissionID int64 `gorm:"column:permission_id;primaryKey"`
	RoleID       int64 `gorm:"column:role_id;primaryKey"`
}

func (RoleHasPermission) TableName() string { return "role_has_permissions" }
