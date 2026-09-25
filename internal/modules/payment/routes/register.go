package routes

import (
	"shopera/internal/helpers"
	paymenthandlers "shopera/internal/modules/payment/handlers"
	paymentrepositories "shopera/internal/modules/payment/repositories"
	paymentservices "shopera/internal/modules/payment/services"
	"shopera/internal/platform/module"
)

// Register wires the payment module and mounts its routes.
func Register(deps module.Deps) {
	handler := paymenthandlers.NewPaymentHandler(
		paymentservices.NewPaymentService(paymentrepositories.NewPaymentProviderRepository(deps.DB), helpers.AppURL()),
	)
	mount(deps.API, handler, deps.Auth)
}
