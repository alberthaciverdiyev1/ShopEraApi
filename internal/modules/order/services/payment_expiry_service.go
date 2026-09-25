package services

import (
	"time"

	"gorm.io/gorm"
	"gorm.io/gorm/clause"

	"shopera/internal/modules/order/models"
)

// PaymentExpirySummary reports what the expiry run did.
type PaymentExpirySummary struct {
	Selected  int64    `json:"selected"`
	Expired   int64    `json:"expired"`
	Skipped   int64    `json:"skipped"`
	Errors    []string `json:"errors"`
	Remaining int64    `json:"remaining"`
}

// PaymentExpiryService releases stock and fails orders left unpaid too long
// (Laravel PendingPaymentExpiryService).
//
// NOTE: Laravel first asked the payment gateway whether the order was actually
// paid; the gateway contract here is callback-only, so that pre-check is not
// performed. Store settlement reversal (Store module) and the push
// notification are also not done yet.
type PaymentExpiryService struct {
	db *gorm.DB
}

func NewPaymentExpiryService(db *gorm.DB) *PaymentExpiryService {
	return &PaymentExpiryService{db: db}
}

// Expire fails card orders older than hours still waiting for payment and
// returns the reserved stock. hours is clamped to ≥1, limit to 1..500.
func (s *PaymentExpiryService) Expire(hours, limit int) (PaymentExpirySummary, error) {
	if hours < 1 {
		hours = 1
	}
	if limit < 1 {
		limit = 1
	}
	if limit > 500 {
		limit = 500
	}
	cutoff := time.Now().Add(-time.Duration(hours) * time.Hour)

	summary := PaymentExpirySummary{Errors: []string{}}

	var ids []int64
	if err := s.pendingQuery(cutoff).Order("o.id asc").Limit(limit).Pluck("o.id", &ids).Error; err != nil {
		return summary, err
	}
	summary.Selected = int64(len(ids))

	for _, id := range ids {
		if err := s.expireOne(id, cutoff, &summary); err != nil {
			summary.Skipped++
			summary.Errors = append(summary.Errors, err.Error())
		}
	}

	if err := s.pendingQuery(cutoff).Count(&summary.Remaining).Error; err != nil {
		return summary, err
	}
	return summary, nil
}

// pendingQuery selects unpaid card orders whose latest status is waiting payment.
func (s *PaymentExpiryService) pendingQuery(cutoff time.Time) *gorm.DB {
	return s.db.Table("orders o").
		Where("o.deleted_at IS NULL AND o.paid_at IS NULL").
		Where("(o.payment_type = ? OR o.payment_type IS NULL)", "CARD").
		Where("o.created_at <= ?", cutoff).
		Where("(SELECT s.status FROM order_statuses s WHERE s.order_id = o.id ORDER BY s.id DESC LIMIT 1) = ?", int(models.StatusWaitingPayment))
}

func (s *PaymentExpiryService) expireOne(id int64, cutoff time.Time, summary *PaymentExpirySummary) error {
	return s.db.Transaction(func(tx *gorm.DB) error {
		var order struct {
			ID            int64
			UserID        int64
			TransactionID *string
			PaidAt        *time.Time
			CreatedAt     time.Time
		}
		err := tx.Table("orders").
			Select("id, user_id, transaction_id, paid_at, created_at").
			Where("id = ?", id).
			Clauses(clause.Locking{Strength: "UPDATE"}).
			Scan(&order).Error
		if err != nil {
			return err
		}
		if order.ID == 0 || order.PaidAt != nil || order.CreatedAt.After(cutoff) {
			summary.Skipped++
			return nil
		}

		var latest struct{ Status *int }
		if err := tx.Table("order_statuses").
			Select("status").Where("order_id = ?", order.ID).
			Order("id desc").Limit(1).Scan(&latest).Error; err != nil {
			return err
		}
		if latest.Status == nil || *latest.Status != int(models.StatusWaitingPayment) {
			summary.Skipped++
			return nil
		}

		// Return reserved stock.
		var items []struct {
			ProductID int64
			Quantity  int
		}
		if err := tx.Table("order_items").
			Select("product_id, quantity").
			Where("order_id = ? AND deleted_at IS NULL", order.ID).
			Scan(&items).Error; err != nil {
			return err
		}
		for _, item := range items {
			if err := tx.Exec(
				"UPDATE products SET stock_count = stock_count + ?, sales_count = GREATEST(sales_count - ?, 0), updated_at = NOW() WHERE id = ?",
				item.Quantity, item.Quantity, item.ProductID,
			).Error; err != nil {
				return err
			}
		}

		// Release the basket rows tied to this transaction.
		if err := tx.Exec(
			"UPDATE baskets SET transaction_id = NULL, updated_at = NOW() WHERE user_id = ? AND transaction_id = ? AND is_ordered = false",
			order.UserID, order.TransactionID,
		).Error; err != nil {
			return err
		}

		// Free any promo codes used on this order.
		if err := tx.Exec(
			"UPDATE used_promo_codes SET transaction_id = NULL, order_id = NULL, updated_at = NOW() WHERE deleted_at IS NULL AND (transaction_id = ? OR order_id = ?)",
			order.TransactionID, order.ID,
		).Error; err != nil {
			return err
		}

		if err := tx.Exec(
			"INSERT INTO order_statuses (order_id, status, created_at, updated_at) VALUES (?, ?, NOW(), NOW())",
			order.ID, int(models.StatusFailed),
		).Error; err != nil {
			return err
		}

		summary.Expired++
		return nil
	})
}
