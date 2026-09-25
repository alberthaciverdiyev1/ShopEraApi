// Package services holds Balance module business logic.
package services

import (
	"context"
	"fmt"
	"strings"
	"time"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	"shopera/internal/modules/balance/models"
	balancerepositories "shopera/internal/modules/balance/repositories"
	balancerequests "shopera/internal/modules/balance/requests"
	balanceresponses "shopera/internal/modules/balance/responses"
	paymentcontract "shopera/internal/modules/payment/contract"
	paymentservices "shopera/internal/modules/payment/services"
)

// BalanceService holds the balance business logic.
type BalanceService struct {
	repo     *balancerepositories.BalanceRepository
	payments *paymentservices.PaymentService
}

func NewBalanceService(repo *balancerepositories.BalanceRepository, payments *paymentservices.PaymentService) *BalanceService {
	return &BalanceService{repo: repo, payments: payments}
}

// Deposit creates a balance entry (deposit/bonus/withdrawal).
func (s *BalanceService) Deposit(userID int64, req balancerequests.DepositRequest) (*models.Balance, error) {
	target := userID
	if req.UserID != nil {
		target = *req.UserID
	}

	balanceType := models.TypeDeposit
	if req.Type != nil {
		switch strings.ToLower(*req.Type) {
		case models.TypeBonus:
			balanceType = models.TypeBonus
		case models.TypeWithdrawal:
			balanceType = models.TypeWithdrawal
		}
	}

	balance := &models.Balance{UserID: target, Type: balanceType, Amount: &req.Amount, Note: req.Note}
	if err := s.repo.Create(balance); err != nil {
		return nil, err
	}
	return balance, nil
}

// History returns the user's balance history.
func (s *BalanceService) History(userID int64, isAdmin bool) ([]gin.H, error) {
	items, err := s.repo.History(userID, isAdmin)
	if err != nil {
		return nil, err
	}
	return balanceresponses.Collection(items), nil
}

// Total returns the user's effective balance.
func (s *BalanceService) Total(userID int64) (float64, error) {
	return s.repo.Sum(userID)
}

// Increase starts a card top-up via the active payment provider.
func (s *BalanceService) Increase(ctx context.Context, userID int64, amount float64) (gin.H, error) {
	transactionID := fmt.Sprintf("BLNC-%d", time.Now().UnixNano())
	base := helpers.AppURL()

	redirectURL, err := s.payments.Initiate(ctx, paymentcontract.InitiateRequest{
		OrderID:     transactionID,
		Amount:      amount,
		Currency:    "AZN",
		Description: "Balans Artımı #" + transactionID,
		SuccessURL:  base + "/api/balance/success?transaction_id=" + transactionID,
		ErrorURL:    base + "/api/balance/error?transaction_id=" + transactionID,
	})
	if err != nil {
		return nil, err
	}

	note := "Kartla balans artırımı başlatıldı - " + transactionID
	balance := &models.Balance{
		UserID:           userID,
		TransactionOrder: &transactionID,
		Type:             models.TypeWaiting,
		Amount:           &amount,
		Note:             &note,
	}
	if err := s.repo.Create(balance); err != nil {
		return nil, err
	}

	return gin.H{"payment_url": redirectURL, "transaction_order": transactionID}, nil
}

// Success confirms a waiting top-up (provider verification deferred — see DEFERRED.md).
func (s *BalanceService) Success(transactionID string) (gin.H, error) {
	balance, err := s.repo.FindWaitingByTransaction(transactionID)
	if err != nil {
		return nil, err
	}
	if balance == nil {
		return nil, helpers.NewAppError(404, "Balance record not found.")
	}
	note := "Kartla balans artırımı tamamlandı - " + transactionID
	if err := s.repo.Confirm(balance.ID, note); err != nil {
		return nil, err
	}
	return gin.H{"transaction_order": transactionID, "amount": balance.Amount}, nil
}

// Error discards a waiting top-up.
func (s *BalanceService) Error(transactionID string) (gin.H, error) {
	balance, err := s.repo.FindWaitingByTransaction(transactionID)
	if err != nil {
		return nil, err
	}
	if balance == nil {
		return nil, helpers.NewAppError(404, "Balance record not found.")
	}
	if err := s.repo.Delete(balance.ID); err != nil {
		return nil, err
	}
	return gin.H{"transaction_order": transactionID}, nil
}
