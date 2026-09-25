package services

import (
	"shopera/internal/helpers"
	"shopera/internal/modules/helpandpolicy/models"
	helprepositories "shopera/internal/modules/helpandpolicy/repositories"
	helprequests "shopera/internal/modules/helpandpolicy/requests"
)

// LegalTermService holds the legal-terms business logic.
type LegalTermService struct {
	repo *helprepositories.LegalTermRepository
}

func NewLegalTermService(repo *helprepositories.LegalTermRepository) *LegalTermService {
	return &LegalTermService{repo: repo}
}

// List returns legal terms of the given type (default main_page).
func (s *LegalTermService) List(termType *string) ([]models.LegalTerm, error) {
	return s.repo.List(resolveType(termType))
}

// Update applies changes to a legal term by type.
func (s *LegalTermService) Update(termType string, req helprequests.LegalTermUpdateRequest) (*models.LegalTerm, error) {
	if len(req.HTML) == 0 {
		return nil, helpers.NewAppError(422, "The html field is required.")
	}
	return s.repo.Update(termType, map[string]any{"html": req.HTML})
}

func resolveType(termType *string) string {
	if termType == nil || *termType == "" {
		return "main_page"
	}
	return *termType
}
