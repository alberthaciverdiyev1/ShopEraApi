package httpserver

import (
	"github.com/gin-gonic/gin"
	"gorm.io/gorm"

	"shopera/internal/config"
	"shopera/internal/helpers"
	"shopera/internal/middleware"
	addresshandlers "shopera/internal/modules/address/handlers"
	addressrepositories "shopera/internal/modules/address/repositories"
	addressroutes "shopera/internal/modules/address/routes"
	addressservices "shopera/internal/modules/address/services"
	authhandlers "shopera/internal/modules/auth/handlers"
	authroutes "shopera/internal/modules/auth/routes"
	authservices "shopera/internal/modules/auth/services"
	bannerhandlers "shopera/internal/modules/banner/handlers"
	bannerrepositories "shopera/internal/modules/banner/repositories"
	bannerroutes "shopera/internal/modules/banner/routes"
	bannerservices "shopera/internal/modules/banner/services"
	baskethandlers "shopera/internal/modules/basket/handlers"
	basketrepositories "shopera/internal/modules/basket/repositories"
	basketroutes "shopera/internal/modules/basket/routes"
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
	deliveryhandlers "shopera/internal/modules/delivery/handlers"
	deliveryrepositories "shopera/internal/modules/delivery/repositories"
	deliveryroutes "shopera/internal/modules/delivery/routes"
	deliveryservices "shopera/internal/modules/delivery/services"
	favoritehandlers "shopera/internal/modules/favorite/handlers"
	favoriterepositories "shopera/internal/modules/favorite/repositories"
	favoriteroutes "shopera/internal/modules/favorite/routes"
	favoriteservices "shopera/internal/modules/favorite/services"
	filterhandlers "shopera/internal/modules/filter/handlers"
	filterrepositories "shopera/internal/modules/filter/repositories"
	filterroutes "shopera/internal/modules/filter/routes"
	filterservices "shopera/internal/modules/filter/services"
	helphandlers "shopera/internal/modules/helpandpolicy/handlers"
	helprepositories "shopera/internal/modules/helpandpolicy/repositories"
	helproutes "shopera/internal/modules/helpandpolicy/routes"
	helpservices "shopera/internal/modules/helpandpolicy/services"
	orderhandlers "shopera/internal/modules/order/handlers"
	orderrepositories "shopera/internal/modules/order/repositories"
	orderroutes "shopera/internal/modules/order/routes"
	orderservices "shopera/internal/modules/order/services"
	paymenthandlers "shopera/internal/modules/payment/handlers"
	paymentrepositories "shopera/internal/modules/payment/repositories"
	paymentroutes "shopera/internal/modules/payment/routes"
	paymentservices "shopera/internal/modules/payment/services"
	popuphandlers "shopera/internal/modules/popup/handlers"
	popuprepositories "shopera/internal/modules/popup/repositories"
	popuproutes "shopera/internal/modules/popup/routes"
	popupservices "shopera/internal/modules/popup/services"
	producthandlers "shopera/internal/modules/product/handlers"
	productrepositories "shopera/internal/modules/product/repositories"
	productroutes "shopera/internal/modules/product/routes"
	productservices "shopera/internal/modules/product/services"
	reviewhandlers "shopera/internal/modules/review/handlers"
	reviewrepositories "shopera/internal/modules/review/repositories"
	reviewroutes "shopera/internal/modules/review/routes"
	reviewservices "shopera/internal/modules/review/services"
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

	settingService := settingservices.NewSettingService(settingrepositories.NewSettingRepository(db))

	productHandler := producthandlers.NewProductHandler(
		productservices.NewProductService(productrepositories.NewProductRepository(db), settingService),
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

	settingHandler := settinghandlers.NewSettingHandler(settingService)
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

	reviewHandler := reviewhandlers.NewReviewHandler(
		reviewservices.NewReviewService(reviewrepositories.NewReviewRepository(db)),
	)
	reviewroutes.Register(api, reviewHandler, authMiddleware)

	filterHandler := filterhandlers.NewFilterHandler(
		filterservices.NewFilterService(filterrepositories.NewFilterRepository(db)),
	)
	filterroutes.Register(api, filterHandler)

	basketHandler := baskethandlers.NewBasketHandler(db)
	basketroutes.Register(api, basketHandler, authMiddleware)

	favoriteHandler := favoritehandlers.NewFavoriteHandler(
		favoriteservices.NewFavoriteService(favoriterepositories.NewFavoriteRepository(db), productrepositories.NewProductRepository(db)),
	)
	favoriteroutes.Register(api, favoriteHandler, authMiddleware)

	paymentHandler := paymenthandlers.NewPaymentHandler(
		paymentservices.NewPaymentService(paymentrepositories.NewPaymentProviderRepository(db), helpers.AppURL()),
	)
	paymentroutes.Register(api, paymentHandler, authMiddleware)

	cityRepo := deliveryrepositories.NewCityRepository(db)
	cityHandler := deliveryhandlers.NewCityHandler(deliveryservices.NewCityService(cityRepo))
	priceHandler := deliveryhandlers.NewDeliveryPriceHandler(
		deliveryservices.NewDeliveryPriceService(deliveryrepositories.NewDeliveryPriceRepository(db), cityRepo),
	)
	pickupHandler := deliveryhandlers.NewPickupPointHandler(
		deliveryservices.NewPickupPointService(deliveryrepositories.NewPickupPointRepository(db)),
	)
	infoHandler := deliveryhandlers.NewDeliveryInfoHandler(
		deliveryservices.NewDeliveryInfoService(deliveryrepositories.NewDeliveryInfoRepository(db)),
	)
	deliveryroutes.Register(api, cityHandler, priceHandler, pickupHandler, infoHandler, authMiddleware)

	addressHandler := addresshandlers.NewAddressHandler(
		addressservices.NewAddressService(addressrepositories.NewAddressRepository(db), cityRepo),
	)
	addressroutes.Register(api, addressHandler, authMiddleware)

	orderHandler := orderhandlers.NewOrderHandler(orderservices.NewOrderService(
		orderrepositories.NewOrderRepository(db),
		basketrepositories.NewBasketRepository(db),
		productrepositories.NewProductRepository(db),
		addressrepositories.NewAddressRepository(db),
		deliveryrepositories.NewCityRepository(db),
		deliveryrepositories.NewDeliveryPriceRepository(db),
		deliveryrepositories.NewPickupPointRepository(db),
	))
	orderroutes.Register(api, orderHandler, authMiddleware)
}
