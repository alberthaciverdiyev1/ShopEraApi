package repositories

import (
	"gorm.io/gorm"

	"shopera/internal/modules/notification/models"
)

// NotificationTokenRepository is the data access for device tokens.
type NotificationTokenRepository struct {
	db *gorm.DB
}

func NewNotificationTokenRepository(db *gorm.DB) *NotificationTokenRepository {
	return &NotificationTokenRepository{db: db}
}

// Upsert stores (or updates) a device token, binding it to the user when known.
func (r *NotificationTokenRepository) Upsert(token string, userID *int64, deviceType string) (*models.NotificationToken, error) {
	var record models.NotificationToken
	err := r.db.Where("token = ?", token).First(&record).Error
	if err == gorm.ErrRecordNotFound {
		record = models.NotificationToken{Token: token, UserID: userID, DeviceType: deviceType, IsActive: true}
		if err := r.db.Create(&record).Error; err != nil {
			return nil, err
		}
		return &record, nil
	}
	if err != nil {
		return nil, err
	}

	fields := map[string]any{"user_id": userID, "device_type": deviceType, "is_active": true, "last_used_at": gorm.Expr("NOW()")}
	if err := r.db.Model(&record).Updates(fields).Error; err != nil {
		return nil, err
	}
	return r.FindByToken(token)
}

// FindByToken returns a token record.
func (r *NotificationTokenRepository) FindByToken(token string) (*models.NotificationToken, error) {
	var record models.NotificationToken
	err := r.db.Where("token = ?", token).First(&record).Error
	if err == gorm.ErrRecordNotFound {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &record, nil
}

// ActiveTokensForUser returns the user's active device tokens.
func (r *NotificationTokenRepository) ActiveTokensForUser(userID int64) ([]string, error) {
	var tokens []string
	err := r.db.Model(&models.NotificationToken{}).
		Where("user_id = ? AND is_active = ?", userID, true).
		Pluck("token", &tokens).Error
	return tokens, err
}

// Deactivate marks a device token inactive.
func (r *NotificationTokenRepository) Deactivate(token string) error {
	return r.db.Model(&models.NotificationToken{}).Where("token = ?", token).
		Update("is_active", false).Error
}
