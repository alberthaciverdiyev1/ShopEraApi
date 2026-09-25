// Package repositories holds the data access for the Balance model.
package repositories

import (
	"errors"

	"gorm.io/gorm"

	"shopera/internal/modules/balance/models"
)

// BalanceRepository is the data access for balances.
type BalanceRepository struct {
	db *gorm.DB
}

func NewBalanceRepository(db *gorm.DB) *BalanceRepository { return &BalanceRepository{db: db} }

// Create inserts a balance row.
func (r *BalanceRepository) Create(b *models.Balance) error { return r.db.Create(b).Error }

// History returns a user's balance rows (newest first). Waiting rows are
// included only for admins.
func (r *BalanceRepository) History(userID int64, includeWaiting bool) ([]models.Balance, error) {
	db := r.db.Where("user_id = ?", userID)
	if !includeWaiting {
		db = db.Where("type <> ?", models.TypeWaiting)
	}
	var items []models.Balance
	err := db.Order("created_at desc").Find(&items).Error
	return items, err
}

// Sum returns the user's effective balance (credits minus debits, waiting excluded).
func (r *BalanceRepository) Sum(userID int64) (float64, error) {
	var total *float64
	err := r.db.Model(&models.Balance{}).
		Where("user_id = ? AND type <> ?", userID, models.TypeWaiting).
		Select(`COALESCE(SUM(CASE WHEN type IN ('deposit','refund','bonus','referral') THEN amount ELSE -amount END), 0)`).
		Scan(&total).Error
	if err != nil || total == nil {
		return 0, err
	}
	return *total, nil
}

// FindWaitingByTransaction returns the waiting balance for a transaction id.
func (r *BalanceRepository) FindWaitingByTransaction(transactionID string) (*models.Balance, error) {
	var b models.Balance
	err := r.db.Where("transaction_order = ? AND type = ?", transactionID, models.TypeWaiting).First(&b).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &b, nil
}

// Confirm marks a waiting balance as a completed deposit.
func (r *BalanceRepository) Confirm(id int64, note string) error {
	return r.db.Model(&models.Balance{}).Where("id = ?", id).
		Updates(map[string]any{"type": models.TypeDeposit, "note": note}).Error
}

// Delete removes a balance row.
func (r *BalanceRepository) Delete(id int64) error {
	return r.db.Delete(&models.Balance{}, id).Error
}
