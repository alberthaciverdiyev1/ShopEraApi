package services

import (
	"shopera/internal/modules/notification/models"
	notificationrepositories "shopera/internal/modules/notification/repositories"
	notificationrequests "shopera/internal/modules/notification/requests"
)

// NotificationTokenService holds the device-token business logic.
type NotificationTokenService struct {
	repo *notificationrepositories.NotificationTokenRepository
}

func NewNotificationTokenService(repo *notificationrepositories.NotificationTokenRepository) *NotificationTokenService {
	return &NotificationTokenService{repo: repo}
}

// SaveToken stores a device token (guests allowed; binds to the user when known).
func (s *NotificationTokenService) SaveToken(userID *int64, req notificationrequests.TokenRequest) (*models.NotificationToken, error) {
	deviceType := "android"
	if req.DeviceType != nil && *req.DeviceType != "" {
		deviceType = *req.DeviceType
	}
	return s.repo.Upsert(req.DeviceToken, userID, deviceType)
}

// DeleteToken deactivates a device token.
func (s *NotificationTokenService) DeleteToken(token string) error {
	return s.repo.Deactivate(token)
}
