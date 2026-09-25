// Package services holds Popup module business logic.
package services

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	popupmodels "shopera/internal/modules/popup/models"
	popuprepositories "shopera/internal/modules/popup/repositories"
	popupresponses "shopera/internal/modules/popup/responses"
)

// PopupService holds the popup business logic.
type PopupService struct {
	repo *popuprepositories.PopupRepository
}

func NewPopupService(repo *popuprepositories.PopupRepository) *PopupService {
	return &PopupService{repo: repo}
}

// List returns popups as a paginated or full list.
func (s *PopupService) List(q helpers.Query) (gin.H, error) {
	items, total, err := s.repo.List(q)
	if err != nil {
		return nil, err
	}
	if q.All {
		return gin.H{"data": popupresponses.Collection(items)}, nil
	}
	return gin.H{"data": popupresponses.Collection(items), "meta": q.Meta(total)}, nil
}

// ShowOne returns the popup flagged for the home page.
func (s *PopupService) ShowOne() (*popupmodels.Popup, error) {
	return s.repo.FindOnHome()
}

// Add stores a popup.
func (s *PopupService) Add(image, video *string, showOnHome bool) error {
	return s.repo.Create(&popupmodels.Popup{Image: image, Video: video, ShowOnHomePage: showOnHome})
}

// ShowHome toggles the home-page popup.
func (s *PopupService) ShowHome(id int64) (*popupmodels.Popup, error) {
	p, err := s.repo.FindByID(id)
	if err != nil {
		return nil, err
	}
	if p == nil {
		return nil, helpers.NewAppError(404, "Popup not found.")
	}
	return s.repo.ToggleHome(id)
}

// Delete removes a popup.
func (s *PopupService) Delete(id int64) error {
	p, err := s.repo.FindByID(id)
	if err != nil {
		return err
	}
	if p == nil {
		return helpers.NewAppError(404, "Popup not found.")
	}
	return s.repo.Delete(id)
}
