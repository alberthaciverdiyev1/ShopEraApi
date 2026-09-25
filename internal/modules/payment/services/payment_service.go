// Package services holds Payment module business logic.
package services

import (
	"context"

	"shopera/internal/helpers"
	"shopera/internal/modules/payment/models"
	paymentproviders "shopera/internal/modules/payment/providers"
	paymentrepositories "shopera/internal/modules/payment/repositories"
	paymentrequests "shopera/internal/modules/payment/requests"
)

// PaymentService holds the payment business logic.
type PaymentService struct {
	repo     *paymentrepositories.PaymentProviderRepository
	registry *paymentproviders.Registry
	baseURL  string
}

func NewPaymentService(repo *paymentrepositories.PaymentProviderRepository, baseURL string) *PaymentService {
	return &PaymentService{repo: repo, registry: paymentproviders.NewRegistry(), baseURL: baseURL}
}

// ListProviders returns providers; admin includes inactive rows and config.
func (s *PaymentService) ListProviders(admin bool) ([]models.PaymentProvider, error) {
	return s.repo.All(!admin)
}

// SaveProvider creates or updates a provider from the admin panel.
func (s *PaymentService) SaveProvider(req paymentrequests.SaveProviderRequest) (*models.PaymentProvider, error) {
	existing, err := s.repo.FindByKey(req.Key)
	if err != nil {
		return nil, err
	}

	fields := map[string]any{"name": req.Name}
	if req.IsActive != nil {
		fields["is_active"] = *req.IsActive
	}
	if req.SortOrder != nil {
		fields["sort_order"] = *req.SortOrder
	}
	if req.Config != nil {
		fields["config"] = req.Config
	}

	if existing == nil {
		provider := &models.PaymentProvider{Key: req.Key, Name: req.Name, IsActive: true, Config: req.Config}
		if req.IsActive != nil {
			provider.IsActive = *req.IsActive
		}
		if req.SortOrder != nil {
			provider.SortOrder = *req.SortOrder
		}
		if err := s.repo.Create(provider); err != nil {
			return nil, err
		}
		return provider, nil
	}
	return s.repo.Update(existing.ID, fields)
}

// CreatePayment starts a payment with the active provider and returns the redirect URL.
func (s *PaymentService) CreatePayment(ctx context.Context, req paymentrequests.CreatePaymentRequest) (string, error) {
	provider, err := s.activeProvider()
	if err != nil {
		return "", err
	}

	return provider.Initiate(ctx, paymentproviders.InitiateRequest{
		OrderID:     req.OrderID,
		Amount:      req.Amount,
		Currency:    "AZN",
		Description: "Order " + req.OrderID,
		SuccessURL:  s.baseURL + "/api/payment/success?transaction_id=" + req.OrderID,
		ErrorURL:    s.baseURL + "/api/payment/error?transaction_id=" + req.OrderID,
		Language:    req.Language,
	}, provider.record.Config)
}

// HandleCallback verifies a provider callback and returns its result.
func (s *PaymentService) HandleCallback(params map[string]string) (*paymentproviders.CallbackResult, error) {
	provider, err := s.providerFor(params)
	if err != nil {
		return nil, err
	}
	return provider.VerifyCallback(params, provider.record.Config)
}

func (s *PaymentService) activeProvider() (providerWithConfig, error) {
	record, err := s.repo.Active()
	if err != nil {
		return providerWithConfig{}, err
	}
	if record == nil {
		return providerWithConfig{}, helpers.NewAppError(422, "No active payment provider configured.")
	}
	impl, ok := s.registry.Get(record.Key)
	if !ok {
		return providerWithConfig{}, helpers.NewAppError(422, "Unsupported payment provider.")
	}
	return providerWithConfig{Provider: impl, record: record}, nil
}

func (s *PaymentService) providerFor(params map[string]string) (providerWithConfig, error) {
	key := params["provider"]
	if key != "" {
		record, err := s.repo.FindByKey(key)
		if err != nil {
			return providerWithConfig{}, err
		}
		if record == nil {
			return providerWithConfig{}, helpers.NewAppError(422, "Unknown payment provider.")
		}
		impl, ok := s.registry.Get(record.Key)
		if !ok {
			return providerWithConfig{}, helpers.NewAppError(422, "Unsupported payment provider.")
		}
		return providerWithConfig{Provider: impl, record: record}, nil
	}
	return s.activeProvider()
}

// providerWithConfig pairs a provider implementation with its stored config.
type providerWithConfig struct {
	paymentproviders.Provider
	record *models.PaymentProvider
}
