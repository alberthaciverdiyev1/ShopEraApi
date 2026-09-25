package routes

import (
	bannerhandlers "shopera/internal/modules/banner/handlers"
	bannerrepositories "shopera/internal/modules/banner/repositories"
	bannerservices "shopera/internal/modules/banner/services"
	"shopera/internal/platform/module"
)

// Register wires the banner module and mounts its routes.
func Register(deps module.Deps) {
	handler := bannerhandlers.NewBannerHandler(
		bannerservices.NewBannerService(bannerrepositories.NewBannerRepository(deps.DB)),
	)
	mount(deps.API, handler, deps.Auth)
}
