// Package responses holds Chat module API response shapes.
package responses

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	"shopera/internal/modules/chat/models"
	chatrepositories "shopera/internal/modules/chat/repositories"
)

// MessageJSON maps a message to its API shape.
func MessageJSON(m models.Message) gin.H {
	attachments := make([]gin.H, 0, len(m.Attachments))
	for _, a := range m.Attachments {
		attachments = append(attachments, gin.H{"id": a.ID, "url": helpers.StorageURL(a.Path)})
	}
	return gin.H{
		"id":              m.ID,
		"conversation_id": m.ConversationID,
		"sender_type":     m.SenderType,
		"sender_id":       m.SenderID,
		"message":         m.Message,
		"is_read":         m.IsRead,
		"created_at":      m.CreatedAt,
		"attachments":     attachments,
	}
}

// MessageCollection maps messages to their API shape.
func MessageCollection(items []models.Message) []gin.H {
	out := make([]gin.H, 0, len(items))
	for _, m := range items {
		out = append(out, MessageJSON(m))
	}
	return out
}

// ConversationJSON maps a conversation row to its admin API shape.
func ConversationJSON(row chatrepositories.ConversationRow) gin.H {
	return gin.H{
		"id":              row.ID,
		"user_id":         row.UserID,
		"user_name":       row.UserName,
		"user_surname":    row.UserSurname,
		"user_email":      row.UserEmail,
		"admin_id":        row.AdminID,
		"last_message_at": row.LastMessageAt,
		"unread_count":    row.UnreadCount,
	}
}

// AutoReplyJSON maps an auto reply to its API shape.
func AutoReplyJSON(a models.AutoReply) gin.H {
	return gin.H{
		"id":         a.ID,
		"question":   a.Question,
		"answer":     a.Answer,
		"created_at": a.CreatedAt,
		"updated_at": a.UpdatedAt,
	}
}
