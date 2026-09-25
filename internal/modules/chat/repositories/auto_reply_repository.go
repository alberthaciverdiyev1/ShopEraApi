package repositories

import (
	"errors"
	"strings"

	"gorm.io/gorm"

	"shopera/internal/modules/chat/models"
)

// AutoReplyRepository is the data access for auto replies.
type AutoReplyRepository struct {
	db *gorm.DB
}

func NewAutoReplyRepository(db *gorm.DB) *AutoReplyRepository { return &AutoReplyRepository{db: db} }

// All returns every auto reply.
func (r *AutoReplyRepository) All() ([]models.AutoReply, error) {
	var items []models.AutoReply
	err := r.db.Order("id desc").Find(&items).Error
	return items, err
}

// FindByID returns an auto reply by id.
func (r *AutoReplyRepository) FindByID(id int64) (*models.AutoReply, error) {
	var a models.AutoReply
	err := r.db.First(&a, id).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &a, nil
}

// Match returns the first auto reply whose question contains the given text.
// TODO: replace with embedding similarity (deferred).
func (r *AutoReplyRepository) Match(text string) (*models.AutoReply, error) {
	var items []models.AutoReply
	if err := r.db.Find(&items).Error; err != nil {
		return nil, err
	}
	lower := strings_ToLower(text)
	for i := range items {
		for _, question := range items[i].Question {
			if question != "" && strings_Contains(lower, strings_ToLower(question)) {
				return &items[i], nil
			}
		}
	}
	return nil, nil
}

// Create inserts an auto reply.
func (r *AutoReplyRepository) Create(a *models.AutoReply) error { return r.db.Create(a).Error }

// Update applies field changes.
func (r *AutoReplyRepository) Update(id int64, fields map[string]any) (*models.AutoReply, error) {
	if err := r.db.Model(&models.AutoReply{}).Where("id = ?", id).Updates(fields).Error; err != nil {
		return nil, err
	}
	return r.FindByID(id)
}

// Delete soft-deletes an auto reply.
func (r *AutoReplyRepository) Delete(id int64) error {
	return r.db.Delete(&models.AutoReply{}, id).Error
}

func strings_ToLower(s string) string   { return strings.ToLower(s) }
func strings_Contains(a, b string) bool { return strings.Contains(a, b) }
