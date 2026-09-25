// Package repositories holds the data access for notifications.
package repositories

import (
	"errors"
	"strings"

	"gorm.io/gorm"

	"shopera/internal/helpers"
	"shopera/internal/modules/notification/models"
)

// NotificationRepository is the data access for notifications.
type NotificationRepository struct {
	db *gorm.DB
}

func NewNotificationRepository(db *gorm.DB) *NotificationRepository {
	return &NotificationRepository{db: db}
}

// Create inserts a notification and attaches the given users (unless all).
func (r *NotificationRepository) Create(n *models.Notification, userIDs []int64) error {
	return r.db.Transaction(func(tx *gorm.DB) error {
		if err := tx.Create(n).Error; err != nil {
			return err
		}
		if !n.All && len(userIDs) > 0 {
			return attachUsers(tx, n.ID, userIDs)
		}
		return nil
	})
}

// ListForUser returns notifications visible to a user (own, all, or attached).
func (r *NotificationRepository) ListForUser(userID int64, q helpers.Query) ([]models.Notification, int64, error) {
	db := r.db.Model(&models.Notification{}).Where(
		"user_id = ? OR all = ? OR EXISTS (SELECT 1 FROM notification_user nu WHERE nu.notification_id = notifications.id AND nu.user_id = ?)",
		userID, true, userID,
	)

	var total int64
	if err := db.Count(&total).Error; err != nil {
		return nil, 0, err
	}
	var items []models.Notification
	err := q.ApplyPage(db.Order("created_at desc")).Find(&items).Error
	return items, total, err
}

// ListAdmin returns notifications for the admin panel.
func (r *NotificationRepository) ListAdmin(q helpers.Query, source string) ([]models.Notification, int64, error) {
	db := r.db.Model(&models.Notification{})
	if source != "all" {
		db = db.Where("source = ?", source)
	}
	if search := strings.TrimSpace(q.Search); search != "" {
		like := "%" + strings.ToLower(search) + "%"
		db = db.Where("lower(title) LIKE ? OR lower(body) LIKE ?", like, like)
	}

	var total int64
	if err := db.Count(&total).Error; err != nil {
		return nil, 0, err
	}
	var items []models.Notification
	err := q.ApplyPage(db.Order("created_at desc")).Find(&items).Error
	return items, total, err
}

// FindByID returns a notification by id.
func (r *NotificationRepository) FindByID(id int64) (*models.Notification, error) {
	var n models.Notification
	err := r.db.First(&n, id).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &n, nil
}

// Delete detaches users and removes the notification.
func (r *NotificationRepository) Delete(id int64) error {
	return r.db.Transaction(func(tx *gorm.DB) error {
		if err := tx.Exec("DELETE FROM notification_user WHERE notification_id = ?", id).Error; err != nil {
			return err
		}
		return tx.Delete(&models.Notification{}, id).Error
	})
}

func attachUsers(tx *gorm.DB, notificationID int64, userIDs []int64) error {
	for _, userID := range userIDs {
		if err := tx.Exec(
			"INSERT INTO notification_user (notification_id, user_id, created_at, updated_at) VALUES (?, ?, NOW(), NOW())",
			notificationID, userID,
		).Error; err != nil {
			return err
		}
	}
	return nil
}
