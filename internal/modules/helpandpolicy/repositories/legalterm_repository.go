package repositories

import (
	"errors"

	"gorm.io/gorm"

	"shopera/internal/modules/helpandpolicy/models"
)

// LegalTermRepository is the data access for the LegalTerm model.
type LegalTermRepository struct {
	db *gorm.DB
}

func NewLegalTermRepository(db *gorm.DB) *LegalTermRepository { return &LegalTermRepository{db: db} }

// List returns legal terms of the given type.
func (r *LegalTermRepository) List(termType string) ([]models.LegalTerm, error) {
	var items []models.LegalTerm
	err := r.db.Where("type = ?", termType).Order("id desc").Find(&items).Error
	return items, err
}

// FindByType returns a legal term by type.
func (r *LegalTermRepository) FindByType(termType string) (*models.LegalTerm, error) {
	var t models.LegalTerm
	err := r.db.Where("type = ?", termType).First(&t).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &t, nil
}

// Update applies field changes to a legal term by type.
func (r *LegalTermRepository) Update(termType string, fields map[string]any) (*models.LegalTerm, error) {
	var t models.LegalTerm
	err := r.db.Transaction(func(tx *gorm.DB) error {
		if err := tx.Where("type = ?", termType).First(&t).Error; err != nil {
			return err
		}
		// Map fields go through a struct update so `serializer:json` is applied.
		changed := []string{}
		if m, ok := fields["html"].(map[string]string); ok {
			t.HTML = m
			changed = append(changed, "html")
			delete(fields, "html")
		}
		if len(fields) > 0 {
			if err := tx.Model(&t).Updates(fields).Error; err != nil {
				return err
			}
		}
		if len(changed) > 0 {
			if err := tx.Model(&t).Select(changed).Updates(t).Error; err != nil {
				return err
			}
		}
		return tx.First(&t, t.ID).Error
	})
	if err != nil {
		return nil, err
	}
	return &t, nil
}
