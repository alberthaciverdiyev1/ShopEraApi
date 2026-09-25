package httpserver

import (
	"github.com/gin-gonic/gin"
	"gorm.io/gorm"

	"shopera/internal/config"
	"shopera/internal/middleware"
	authhandlers "shopera/internal/modules/auth/handlers"
	authroutes "shopera/internal/modules/auth/routes"
	authservices "shopera/internal/modules/auth/services"
	bannerhandlers "shopera/internal/modules/banner/handlers"
	bannerrepositories "shopera/internal/modules/banner/repositories"
	bannerroutes "shopera/internal/modules/banner/routes"
	bannerservices "shopera/internal/modules/banner/services"
	brandhandlers "shopera/internal/modules/brand/handlers"
	brandrepositories "shopera/internal/modules/brand/repositories"
	brandroutes "shopera/internal/modules/brand/routes"
	brandservices "shopera/internal/modules/brand/services"
	categoryhandlers "shopera/internal/modules/category/handlers"
	categoryrepositories "shopera/internal/modules/category/repositories"
	categoryroutes "shopera/internal/modules/category/routes"
	categoryservices "shopera/internal/modules/category/services"
	colorhandlers "shopera/internal/modules/color/handlers"
	colorrepositories "shopera/internal/modules/color/repositories"
	colorroutes "shopera/internal/modules/color/routes"
	colorservices "shopera/internal/modules/color/services"
	helphandlers "shopera/internal/modules/helpandpolicy/handlers"
	helprepositories "shopera/internal/modules/helpandpolicy/repositories"
	helproutes "shopera/internal/modules/helpandpolicy/routes"
	helpservices "shopera/internal/modules/helpandpolicy/services"
	popuphandlers "shopera/internal/modules/popup/handlers"
	popuprepositories "shopera/internal/modules/popup/repositories"
	popuproutes "shopera/internal/modules/popup/routes"
	popupservices "shopera/internal/modules/popup/services"
	producthandlers "shopera/internal/modules/product/handlers"
	productrepositories "shopera/internal/modules/product/repositories"
	productroutes "shopera/internal/modules/product/routes"
	productservices "shopera/internal/modules/product/services"
	settinghandlers "shopera/internal/modules/setting/handlers"
	settingrepositories "shopera/internal/modules/setting/repositories"
	settingroutes "shopera/internal/modules/setting/routes"
	settingservices "shopera/internal/modules/setting/services"
	sizehandlers "shopera/internal/modules/size/handlers"
	sizerepositories "shopera/internal/modules/size/repositories"
	sizeroutes "shopera/internal/modules/size/routes"
	sizeservices "shopera/internal/modules/size/services"
	userrepositories "shopera/internal/modules/user/repositories"
)

// registerModules wires and mounts every feature module under /api.
func registerModules(api *gin.RouterGroup, cfg *config.Config, db *gorm.DB) {
	authMiddleware := middleware.AuthRequired(cfg.JWT.Secret)

	authHandler := authhandlers.NewAuthHandler(
		authservices.NewAuthService(userrepositories.NewUserRepository(db), cfg),
	)
	authroutes.Register(api, authHandler, authMiddleware)

	productHandler := producthandlers.NewProductHandler(
		productservices.NewProductService(productrepositories.NewProductRepository(db)),
	)
	productroutes.Register(api, productHandler, authMiddleware)

	categoryHandler := categoryhandlers.NewCategoryHandler(
		categoryservices.NewCategoryService(categoryrepositories.NewCategoryRepository(db)),
	)
	categoryroutes.Register(api, categoryHandler, authMiddleware)

	brandHandler := brandhandlers.NewBrandHandler(
		brandservices.NewBrandService(brandrepositories.NewBrandRepository(db)),
	)
	brandroutes.Register(api, brandHandler, authMiddleware)

	colorHandler := colorhandlers.NewColorHandler(
		colorservices.NewColorService(colorrepositories.NewColorRepository(db)),
	)
	colorroutes.Register(api, colorHandler, authMiddleware)

	sizeHandler := sizehandlers.NewSizeHandler(
		sizeservices.NewSizeService(sizerepositories.NewSizeRepository(db)),
	)
	sizeroutes.Register(api, sizeHandler, authMiddleware)

	settingHandler := settinghandlers.NewSettingHandler(
		settingservices.NewSettingService(settingrepositories.NewSettingRepository(db)),
	)
	settingroutes.Register(api, settingHandler, authMiddleware)

	bannerHandler := bannerhandlers.NewBannerHandler(
		bannerservices.NewBannerService(bannerrepositories.NewBannerRepository(db)),
	)
	bannerroutes.Register(api, bannerHandler, authMiddleware)

	popupHandler := popuphandlers.NewPopupHandler(
		popupservices.NewPopupService(popuprepositories.NewPopupRepository(db)),
	)
	popuproutes.Register(api, popupHandler, authMiddleware)

	faqHandler := helphandlers.NewFaqHandler(
		helpservices.NewFaqService(helprepositories.NewFaqRepository(db)),
	)
	helproutes.RegisterFaq(api, faqHandler, authMiddleware)

	legalHandler := helphandlers.NewLegalTermHandler(
		helpservices.NewLegalTermService(helprepositories.NewLegalTermRepository(db)),
	)
	helproutes.RegisterLegalTerms(api, legalHandler, authMiddleware)
}
