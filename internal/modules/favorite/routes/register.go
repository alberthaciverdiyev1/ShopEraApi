package routes

import (
	favoritehandlers "shopera/internal/modules/favorite/handlers"
	favoriterepositories "shopera/internal/modules/favorite/repositories"
	favoriteservices "shopera/internal/modules/favorite/services"
	productrepositories "shopera/internal/modules/product/repositories"
	"shopera/internal/platform/module"
)

// Register wires the favorite module and mounts its routes.
func Register(deps module.Deps) {
	handler := favoritehandlers.NewFavoriteHandler(
		favoriteservices.NewFavoriteService(favoriterepositories.NewFavoriteRepository(deps.DB), productrepositories.NewProductRepository(deps.DB)),
	)
	mount(deps.API, handler, deps.Auth)
}
