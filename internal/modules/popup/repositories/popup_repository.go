// Package repositories holds the data access for the Popup model.
package repositories

import (
	"errors"

	"gorm.io/gorm"

	"shopera/internal/helpers"
	"shopera/internal/modules/popup/models"
)

// PopupRepository is the data access for the Popup model.
type PopupRepository struct {
	db *gorm.DB
}

func NewPopupRepository(db *gorm.DB) *PopupRepository { return &PopupRepository{db: db} }

// List returns popups (newest first), paginated unless full data is requested.
func (r *PopupRepository) List(q helpers.Query) ([]models.Popup, int64, error) {
	db := r.db.Model(&models.Popup{})

	var total int64
	if err := db.Count(&total).Error; err != nil {
		return nil, 0, err
	}

	var items []models.Popup
	err := q.ApplyPage(db.Order("created_at desc").Order("id desc")).Find(&items).Error
	return items, total, err
}

// FindByID returns a popup by id.
func (r *PopupRepository) FindByID(id int64) (*models.Popup, error) {
	var p models.Popup
	err := r.db.First(&p, id).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &p, nil
}

// FindOnHome returns the popup flagged for the home page.
func (r *PopupRepository) FindOnHome() (*models.Popup, error) {
	var p models.Popup
	err := r.db.Where("show_on_home_page = ?", true).First(&p).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &p, nil
}

// Create inserts a popup.
func (r *PopupRepository) Create(p *models.Popup) error {
	return r.db.Create(p).Error
}

// ToggleHome flips the target's flag and clears every other popup.
func (r *PopupRepository) ToggleHome(id int64) (*models.Popup, error) {
	var result models.Popup
	err := r.db.Transaction(func(tx *gorm.DB) error {
		var p models.Popup
		if err := tx.First(&p, id).Error; err != nil {
			return err
		}
		if err := tx.Model(&p).Update("show_on_home_page", !p.ShowOnHomePage).Error; err != nil {
			return err
		}
		if err := tx.Model(&models.Popup{}).Where("id <> ?", id).Update("show_on_home_page", false).Error; err != nil {
			return err
		}
		return tx.First(&result, id).Error
	})
	if err != nil {
		return nil, err
	}
	return &result, nil
}

// Delete removes a popup.
func (r *PopupRepository) Delete(id int64) error {
	return r.db.Delete(&models.Popup{}, id).Error
}
