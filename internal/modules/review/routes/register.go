package routes

import (
	reviewhandlers "shopera/internal/modules/review/handlers"
	reviewrepositories "shopera/internal/modules/review/repositories"
	reviewservices "shopera/internal/modules/review/services"
	"shopera/internal/platform/module"
)

// Register wires the review module and mounts its routes.
func Register(deps module.Deps) {
	handler := reviewhandlers.NewReviewHandler(
		reviewservices.NewReviewService(reviewrepositories.NewReviewRepository(deps.DB)),
	)
	mount(deps.API, handler, deps.Auth)
}
