// Package services holds Chat module business logic.
package services

import (
	chatmodels "shopera/internal/modules/chat/models"
	chatrepositories "shopera/internal/modules/chat/repositories"
	chatrequests "shopera/internal/modules/chat/requests"
)

// AutoReplyService holds the auto-reply business logic.
type AutoReplyService struct {
	repo *chatrepositories.AutoReplyRepository
}

func NewAutoReplyService(repo *chatrepositories.AutoReplyRepository) *AutoReplyService {
	return &AutoReplyService{repo: repo}
}

// List returns every auto reply.
func (s *AutoReplyService) List() ([]chatmodels.AutoReply, error) { return s.repo.All() }

// Add creates an auto reply.
func (s *AutoReplyService) Add(req chatrequests.AutoReplySaveRequest) (*chatmodels.AutoReply, error) {
	reply := &chatmodels.AutoReply{Question: req.Question, Answer: req.Answer}
	if err := s.repo.Create(reply); err != nil {
		return nil, err
	}
	return reply, nil
}

// Update changes an auto reply.
func (s *AutoReplyService) Update(id int64, req chatrequests.AutoReplySaveRequest) (*chatmodels.AutoReply, error) {
	return s.repo.Update(id, map[string]any{"question": req.Question, "answer": req.Answer})
}

// Delete removes an auto reply.
func (s *AutoReplyService) Delete(id int64) error { return s.repo.Delete(id) }

// GetAutoResponse returns the best matching answer for a user message (or nil).
func (s *AutoReplyService) GetAutoResponse(text string) (*string, error) {
	reply, err := s.repo.Match(text)
	if err != nil || reply == nil {
		return nil, err
	}
	for _, lang := range []string{"az", "en", "ru", "tr"} {
		if answer := reply.Answer[lang]; answer != "" {
			return &answer, nil
		}
	}
	return nil, nil
}
