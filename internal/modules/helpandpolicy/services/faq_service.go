// Package services holds HelpAndPolicy module business logic.
package services

import (
	"shopera/internal/helpers"
	helphelpers "shopera/internal/modules/helpandpolicy/helpers"
	"shopera/internal/modules/helpandpolicy/models"
	helprepositories "shopera/internal/modules/helpandpolicy/repositories"
	helprequests "shopera/internal/modules/helpandpolicy/requests"
)

// FaqService holds the faq business logic.
type FaqService struct {
	repo *helprepositories.FaqRepository
}

func NewFaqService(repo *helprepositories.FaqRepository) *FaqService {
	return &FaqService{repo: repo}
}

// List returns faqs, optionally filtered by type.
func (s *FaqService) List(faqType *string) ([]models.Faq, error) {
	return s.repo.List(faqType)
}

// Add creates a faq, filling translations.
func (s *FaqService) Add(req helprequests.FaqSaveRequest) (*models.Faq, error) {
	if req.Title == nil || req.Title["az"] == "" {
		return nil, helpers.NewAppError(422, "The title.az field is required.")
	}
	if req.Type == nil || *req.Type == "" {
		return nil, helpers.NewAppError(422, "The type field is required.")
	}

	faq := &models.Faq{
		Title:       helphelpers.FillTitle(req.Title),
		Description: helphelpers.FillUcfirst(req.Description),
		Type:        *req.Type,
	}
	if err := s.repo.Create(faq); err != nil {
		return nil, err
	}
	return faq, nil
}

// Update applies changes to a faq.
func (s *FaqService) Update(id int64, req helprequests.FaqSaveRequest) (*models.Faq, error) {
	fields := map[string]any{}
	if len(req.Title) > 0 {
		fields["title"] = helphelpers.FillTitle(req.Title)
	}
	if len(req.Description) > 0 {
		fields["description"] = helphelpers.FillUcfirst(req.Description)
	}
	if req.Type != nil {
		fields["type"] = *req.Type
	}

	faq, err := s.repo.Update(id, fields)
	if err != nil {
		return nil, err
	}
	if faq == nil {
		return nil, helpers.NewAppError(404, "Faq not found.")
	}
	return faq, nil
}

// Delete removes a faq.
func (s *FaqService) Delete(id int64) error {
	faq, err := s.repo.FindByID(id)
	if err != nil {
		return err
	}
	if faq == nil {
		return helpers.NewAppError(404, "Faq not found.")
	}
	return s.repo.Delete(id)
}
