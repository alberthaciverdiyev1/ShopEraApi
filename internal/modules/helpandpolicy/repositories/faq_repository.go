// Package repositories holds the data access for HelpAndPolicy models.
package repositories

import (
	"errors"

	"gorm.io/gorm"

	"shopera/internal/modules/helpandpolicy/models"
)

// FaqRepository is the data access for the Faq model.
type FaqRepository struct {
	db *gorm.DB
}

func NewFaqRepository(db *gorm.DB) *FaqRepository { return &FaqRepository{db: db} }

// List returns faqs (optionally filtered by type), newest first.
func (r *FaqRepository) List(faqType *string) ([]models.Faq, error) {
	db := r.db.Model(&models.Faq{})
	if faqType != nil {
		db = db.Where("type = ?", *faqType)
	}
	var items []models.Faq
	err := db.Order("id desc").Find(&items).Error
	return items, err
}

// FindByID returns a faq by id.
func (r *FaqRepository) FindByID(id int64) (*models.Faq, error) {
	var f models.Faq
	err := r.db.First(&f, id).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &f, nil
}

// Create inserts a faq.
func (r *FaqRepository) Create(f *models.Faq) error {
	return r.db.Create(f).Error
}

// Update applies field changes to a faq.
func (r *FaqRepository) Update(id int64, fields map[string]any) (*models.Faq, error) {
	var f models.Faq
	err := r.db.Transaction(func(tx *gorm.DB) error {
		if err := tx.First(&f, id).Error; err != nil {
			return err
		}
		if len(fields) > 0 {
			if err := tx.Model(&f).Updates(fields).Error; err != nil {
				return err
			}
		}
		return tx.First(&f, id).Error
	})
	if err != nil {
		return nil, err
	}
	return &f, nil
}

// Delete removes a faq.
func (r *FaqRepository) Delete(id int64) error {
	return r.db.Delete(&models.Faq{}, id).Error
}
