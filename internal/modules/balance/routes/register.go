package routes

import (
	"shopera/internal/helpers"
	balancehandlers "shopera/internal/modules/balance/handlers"
	balancerepositories "shopera/internal/modules/balance/repositories"
	balanceservices "shopera/internal/modules/balance/services"
	paymentrepositories "shopera/internal/modules/payment/repositories"
	paymentservices "shopera/internal/modules/payment/services"
	"shopera/internal/platform/module"
)

// Register wires the balance module and mounts its routes.
func Register(deps module.Deps) {
	paymentService := paymentservices.NewPaymentService(paymentrepositories.NewPaymentProviderRepository(deps.DB), helpers.AppURL())
	handler := balancehandlers.NewBalanceHandler(
		balanceservices.NewBalanceService(balancerepositories.NewBalanceRepository(deps.DB), paymentService),
	)
	mount(deps.API, handler, deps.Auth)
}
