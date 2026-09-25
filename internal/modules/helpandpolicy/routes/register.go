package routes

import (
	helphandlers "shopera/internal/modules/helpandpolicy/handlers"
	helprepositories "shopera/internal/modules/helpandpolicy/repositories"
	helpservices "shopera/internal/modules/helpandpolicy/services"
	"shopera/internal/platform/module"
)

// Register wires the helpandpolicy module and mounts its routes.
func Register(deps module.Deps) {
	faqHandler := helphandlers.NewFaqHandler(
		helpservices.NewFaqService(helprepositories.NewFaqRepository(deps.DB)),
	)
	mountFaq(deps.API, faqHandler, deps.Auth, deps.Permission)

	legalHandler := helphandlers.NewLegalTermHandler(
		helpservices.NewLegalTermService(helprepositories.NewLegalTermRepository(deps.DB)),
	)
	mountLegalTerms(deps.API, legalHandler, deps.Auth, deps.Permission)
}
