package services

import (
	"context"
	"os"
	"strconv"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	chatmodels "shopera/internal/modules/chat/models"
	chatrepositories "shopera/internal/modules/chat/repositories"
	chatrequests "shopera/internal/modules/chat/requests"
	chatresponses "shopera/internal/modules/chat/responses"
	userrepositories "shopera/internal/modules/user/repositories"
)

// ChatService holds the chat business logic.
type ChatService struct {
	repo       *chatrepositories.ChatRepository
	autoReply  *AutoReplyService
	users      *userrepositories.UserRepository
	adminPhone string
}

func NewChatService(repo *chatrepositories.ChatRepository, autoReply *AutoReplyService, users *userrepositories.UserRepository) *ChatService {
	phone := os.Getenv("ADMIN_PHONE")
	if phone == "" {
		phone = "0708990929"
	}
	return &ChatService{repo: repo, autoReply: autoReply, users: users, adminPhone: phone}
}

// Send stores a message, its attachment and (for users) an auto reply if matched.
func (s *ChatService) Send(ctx context.Context, userID int64, req chatrequests.SendRequest, imagePath *string) error {
	sender, err := s.users.FindByID(userID)
	if err != nil {
		return err
	}
	if sender == nil {
		return helpers.NewAppError(401, "Unauthorized")
	}

	admin, err := s.users.FindByPhone(s.adminPhone)
	if err != nil {
		return err
	}
	if admin == nil {
		return helpers.NewAppError(404, "Admin not found")
	}

	isAdmin := sender.ID == admin.ID
	if isAdmin && req.TargetUserID == nil {
		return helpers.NewAppError(422, "Target user id is required for admin")
	}

	targetUserID := userID
	if isAdmin {
		targetUserID = *req.TargetUserID
	}

	conversation, err := s.repo.FindOrCreateConversation(targetUserID, admin.ID)
	if err != nil {
		return err
	}

	senderType := "user"
	if isAdmin {
		senderType = "admin"
	}
	message := &chatmodels.Message{
		ConversationID: conversation.ID,
		SenderType:     senderType,
		SenderID:       userID,
		Message:        req.Message,
		IsRead:         false,
	}
	if err := s.repo.CreateMessage(message); err != nil {
		return err
	}

	if imagePath != nil {
		if err := s.repo.CreateAttachment(&chatmodels.MessageAttachment{MessageID: message.ID, Path: *imagePath}); err != nil {
			return err
		}
	}

	if !isAdmin && req.Message != nil && *req.Message != "" {
		answer, err := s.autoReply.GetAutoResponse(*req.Message)
		if err != nil {
			return err
		}
		if answer != nil {
			_ = s.repo.CreateMessage(&chatmodels.Message{
				ConversationID: conversation.ID,
				SenderType:     "admin",
				SenderID:       admin.ID,
				Message:        answer,
				IsRead:         false,
			})
		}
	}

	return s.repo.Touch(conversation.ID)
}

// Messages returns a conversation's messages (ascending).
func (s *ChatService) Messages(conversationID int64) ([]gin.H, error) {
	items, err := s.repo.Messages(conversationID)
	if err != nil {
		return nil, err
	}
	return chatresponses.MessageCollection(items), nil
}

// MessageList returns the user's own conversation messages (newest first).
func (s *ChatService) MessageList(userID int64) ([]gin.H, error) {
	conversation, err := s.repo.FirstConversationForUser(userID)
	if err != nil {
		return nil, err
	}
	if conversation == nil {
		return []gin.H{}, nil
	}
	items, err := s.repo.Messages(conversation.ID)
	if err != nil {
		return nil, err
	}
	// reverse to newest-first
	for i, j := 0, len(items)-1; i < j; i, j = i+1, j-1 {
		items[i], items[j] = items[j], items[i]
	}
	return chatresponses.MessageCollection(items), nil
}

// MarkAsRead marks a conversation as read.
func (s *ChatService) MarkAsRead(conversationID int64) error {
	return s.repo.MarkRead(conversationID)
}

// DeleteMessage removes a message and its attachments (files included).
func (s *ChatService) DeleteMessage(id int64) error {
	message, err := s.repo.FindMessage(id)
	if err != nil {
		return err
	}
	if message == nil {
		return helpers.NewAppError(404, "Message not found.")
	}
	for _, a := range message.Attachments {
		_ = helpers.DeleteFile(a.Path)
	}
	return s.repo.DeleteMessage(id)
}

// DeleteConversation removes a conversation with its messages and attachments.
func (s *ChatService) DeleteConversation(id int64) error {
	conversation, err := s.repo.FindConversation(id)
	if err != nil {
		return err
	}
	if conversation == nil {
		return nil // already deleted
	}
	messages, err := s.repo.Messages(id)
	if err != nil {
		return err
	}
	for _, m := range messages {
		for _, a := range m.Attachments {
			_ = helpers.DeleteFile(a.Path)
		}
	}
	return s.repo.DeleteConversation(id)
}

// ConversationList returns conversations for the admin panel.
func (s *ChatService) ConversationList(q helpers.Query) (gin.H, error) {
	rows, total, err := s.repo.ListConversations(q)
	if err != nil {
		return nil, err
	}
	out := make([]gin.H, 0, len(rows))
	for _, row := range rows {
		out = append(out, chatresponses.ConversationJSON(row))
	}
	return gin.H{"data": out, "meta": q.Meta(total)}, nil
}

var _ = strconv.Itoa
