package services

import (
	"context"
	"strings"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	"shopera/internal/modules/notification/models"
	notificationrepositories "shopera/internal/modules/notification/repositories"
	notificationrequests "shopera/internal/modules/notification/requests"
	notificationresponses "shopera/internal/modules/notification/responses"
)

// NotificationService holds the notification business logic.
type NotificationService struct {
	repo   *notificationrepositories.NotificationRepository
	tokens *notificationrepositories.NotificationTokenRepository
	pusher Pusher
}

func NewNotificationService(
	repo *notificationrepositories.NotificationRepository,
	tokens *notificationrepositories.NotificationTokenRepository,
	pusher Pusher,
) *NotificationService {
	return &NotificationService{repo: repo, tokens: tokens, pusher: pusher}
}

// List returns the user's notifications.
func (s *NotificationService) List(userID int64, q helpers.Query) (gin.H, error) {
	items, total, err := s.repo.ListForUser(userID, q)
	if err != nil {
		return nil, err
	}
	return gin.H{"data": notificationresponses.Collection(items), "meta": q.Meta(total)}, nil
}

// ListAdmin returns notifications for the admin panel.
func (s *NotificationService) ListAdmin(q helpers.Query, source string) (gin.H, error) {
	items, total, err := s.repo.ListAdmin(q, source)
	if err != nil {
		return nil, err
	}
	return gin.H{"data": notificationresponses.Collection(items), "meta": q.Meta(total)}, nil
}

// Delete removes a notification.
func (s *NotificationService) Delete(id int64) error {
	existing, err := s.repo.FindByID(id)
	if err != nil {
		return err
	}
	if existing == nil {
		return helpers.NewAppError(404, "Notification not found.")
	}
	return s.repo.Delete(id)
}

// Send stores a notification and pushes it to the recipients' devices.
func (s *NotificationService) Send(ctx context.Context, req notificationrequests.SendRequest) (*models.Notification, error) {
	all := req.All != nil && *req.All

	notification := &models.Notification{
		Title:  strings.ToLower(req.Title),
		Body:   strings.ToLower(req.Body),
		All:    all,
		Data:   req.Data,
		Icon:   req.Icon,
		URL:    req.URL,
		Source: "admin",
	}
	if err := s.repo.Create(notification, req.Users); err != nil {
		return nil, err
	}

	s.pushToUsers(ctx, req, notification)
	return notification, nil
}

// pushToUsers best-effort pushes to each recipient's active devices.
func (s *NotificationService) pushToUsers(ctx context.Context, req notificationrequests.SendRequest, notification *models.Notification) {
	data := map[string]string{}
	for k, v := range req.Data {
		if s, ok := v.(string); ok {
			data[k] = s
		}
	}

	for _, userID := range req.Users {
		tokens, err := s.tokens.ActiveTokensForUser(userID)
		if err != nil {
			continue
		}
		for _, token := range tokens {
			_ = s.pusher.Send(ctx, token, notification.Title, notification.Body, data)
		}
	}
}
