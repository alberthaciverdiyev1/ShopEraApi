// Package repositories holds the data access for chat.
package repositories

import (
	"errors"

	"gorm.io/gorm"

	"shopera/internal/helpers"
	"shopera/internal/modules/chat/models"
)

// ChatRepository is the data access for conversations and messages.
type ChatRepository struct {
	db *gorm.DB
}

func NewChatRepository(db *gorm.DB) *ChatRepository { return &ChatRepository{db: db} }

// FindOrCreateConversation returns the (user, admin) conversation, creating it if needed.
func (r *ChatRepository) FindOrCreateConversation(userID, adminID int64) (*models.Conversation, error) {
	var c models.Conversation
	err := r.db.Where("user_id = ? AND admin_id = ?", userID, adminID).First(&c).Error
	if err == nil {
		return &c, nil
	}
	if !errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, err
	}
	c = models.Conversation{UserID: userID, AdminID: adminID}
	if err := r.db.Create(&c).Error; err != nil {
		return nil, err
	}
	return &c, nil
}

// FirstConversationForUser returns the user's first conversation.
func (r *ChatRepository) FirstConversationForUser(userID int64) (*models.Conversation, error) {
	var c models.Conversation
	err := r.db.Where("user_id = ?", userID).First(&c).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &c, nil
}

// FindConversation returns a conversation by id.
func (r *ChatRepository) FindConversation(id int64) (*models.Conversation, error) {
	var c models.Conversation
	err := r.db.First(&c, id).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &c, nil
}

// CreateMessage inserts a message.
func (r *ChatRepository) CreateMessage(m *models.Message) error { return r.db.Create(m).Error }

// CreateAttachment inserts an attachment.
func (r *ChatRepository) CreateAttachment(a *models.MessageAttachment) error {
	return r.db.Create(a).Error
}

// Messages returns a conversation's messages (ascending).
func (r *ChatRepository) Messages(conversationID int64) ([]models.Message, error) {
	var items []models.Message
	err := r.db.Preload("Attachments").Where("conversation_id = ?", conversationID).
		Order("id asc").Find(&items).Error
	return items, err
}

// MarkRead marks a conversation's messages as read.
func (r *ChatRepository) MarkRead(conversationID int64) error {
	return r.db.Model(&models.Message{}).
		Where("conversation_id = ? AND is_read = ?", conversationID, false).
		Update("is_read", true).Error
}

// Touch updates last_message_at.
func (r *ChatRepository) Touch(conversationID int64) error {
	return r.db.Model(&models.Conversation{}).Where("id = ?", conversationID).
		Update("last_message_at", gorm.Expr("NOW()")).Error
}

// FindMessage returns a message with attachments.
func (r *ChatRepository) FindMessage(id int64) (*models.Message, error) {
	var m models.Message
	err := r.db.Preload("Attachments").First(&m, id).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &m, nil
}

// DeleteMessage removes a message and its attachments.
func (r *ChatRepository) DeleteMessage(id int64) error {
	return r.db.Transaction(func(tx *gorm.DB) error {
		if err := tx.Where("message_id = ?", id).Delete(&models.MessageAttachment{}).Error; err != nil {
			return err
		}
		return tx.Delete(&models.Message{}, id).Error
	})
}

// DeleteConversation cascades messages and attachments.
func (r *ChatRepository) DeleteConversation(id int64) error {
	return r.db.Transaction(func(tx *gorm.DB) error {
		if err := tx.Exec("DELETE FROM message_attachments WHERE message_id IN (SELECT id FROM messages WHERE conversation_id = ?)", id).Error; err != nil {
			return err
		}
		if err := tx.Exec("DELETE FROM messages WHERE conversation_id = ?", id).Error; err != nil {
			return err
		}
		return tx.Delete(&models.Conversation{}, id).Error
	})
}

// ConversationRow is a conversation list row.
type ConversationRow struct {
	models.Conversation
	UserName    *string
	UserSurname *string
	UserEmail   *string
	UnreadCount int64
}

// ListConversations returns conversations with the user and unread count (admin).
func (r *ChatRepository) ListConversations(q helpers.Query) ([]ConversationRow, int64, error) {
	base := r.db.Table("conversations c").
		Joins("LEFT JOIN users u ON u.id = c.user_id").
		Where("u.deleted_at IS NULL OR u.deleted_at IS NULL")
	if q.Search != "" {
		like := "%" + q.Search + "%"
		base = base.Where("u.name ILIKE ? OR u.surname ILIKE ? OR u.email ILIKE ? OR u.phone ILIKE ?", like, like, like, like)
	}

	var total int64
	if err := base.Count(&total).Error; err != nil {
		return nil, 0, err
	}

	var rows []ConversationRow
	err := base.Select(`c.id, c.user_id, c.admin_id, c.last_message_at, c.created_at, c.updated_at,
			u.name AS user_name, u.surname AS user_surname, u.email AS user_email,
			(SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.sender_type = 'user' AND m.is_read = false) AS unread_count`).
		Order("unread_count desc").Order("c.last_message_at desc").
		Limit(q.PerPage).Offset(q.Offset()).
		Scan(&rows).Error
	return rows, total, err
}
