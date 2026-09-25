package routes

import (
	categoryhandlers "shopera/internal/modules/category/handlers"
	categoryrepositories "shopera/internal/modules/category/repositories"
	categoryservices "shopera/internal/modules/category/services"
	"shopera/internal/platform/module"
)

// Register wires the category module and mounts its routes.
func Register(deps module.Deps) {
	handler := categoryhandlers.NewCategoryHandler(
		categoryservices.NewCategoryService(categoryrepositories.NewCategoryRepository(deps.DB)),
	)
	mount(deps.API, handler, deps.Auth)
}
