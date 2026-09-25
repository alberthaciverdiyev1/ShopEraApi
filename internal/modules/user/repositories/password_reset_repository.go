package repositories

import (
	"errors"

	"gorm.io/gorm"

	"shopera/internal/modules/user/models"
)

// PasswordResetRepository is the data access for admin password-reset requests.
type PasswordResetRepository struct {
	db *gorm.DB
}

func NewPasswordResetRepository(db *gorm.DB) *PasswordResetRepository {
	return &PasswordResetRepository{db: db}
}

// FirstOrCreatePending creates a pending request for a user if none exists.
func (r *PasswordResetRepository) FirstOrCreatePending(userID int64, phone string, note *string) error {
	var existing models.PasswordResetRequest
	err := r.db.Where("user_id = ? AND status = ?", userID, models.ResetStatusPending).First(&existing).Error
	if err == nil {
		return nil
	}
	if !errors.Is(err, gorm.ErrRecordNotFound) {
		return err
	}
	return r.db.Create(&models.PasswordResetRequest{
		UserID: userID, Phone: phone, Status: models.ResetStatusPending, Note: note,
	}).Error
}

// List returns requests filtered by status/search (paginated).
func (r *PasswordResetRepository) List(status *string, search string, limit, offset int) ([]models.PasswordResetRequest, int64, error) {
	db := r.db.Model(&models.PasswordResetRequest{})
	if status != nil {
		db = db.Where("status = ?", *status)
	}
	if search != "" {
		like := "%" + search + "%"
		db = db.Where("phone LIKE ? OR user_id IN (SELECT id FROM users WHERE name ILIKE ? OR surname ILIKE ?)", like, like, like)
	}

	var total int64
	if err := db.Count(&total).Error; err != nil {
		return nil, 0, err
	}
	var items []models.PasswordResetRequest
	err := db.Order("id desc").Limit(limit).Offset(offset).Find(&items).Error
	return items, total, err
}

// FindPending returns a pending request by id.
func (r *PasswordResetRepository) FindPending(id int64) (*models.PasswordResetRequest, error) {
	var req models.PasswordResetRequest
	err := r.db.Where("id = ? AND status = ?", id, models.ResetStatusPending).First(&req).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &req, nil
}

// Mark updates a request's status/resolver.
func (r *PasswordResetRepository) Mark(id int64, status string, resolvedBy int64) error {
	return r.db.Model(&models.PasswordResetRequest{}).Where("id = ?", id).
		Updates(map[string]any{"status": status, "resolved_by": resolvedBy, "resolved_at": gorm.Expr("NOW()")}).Error
}
