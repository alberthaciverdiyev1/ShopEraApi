package httpserver

import (
	"github.com/gin-gonic/gin"
	"gorm.io/gorm"

	"shopera/internal/config"
	"shopera/internal/middleware"
	rolepermissionrepositories "shopera/internal/modules/rolepermission/repositories"
	"shopera/internal/platform/module"

	addressroutes "shopera/internal/modules/address/routes"
	authroutes "shopera/internal/modules/auth/routes"
	balanceroutes "shopera/internal/modules/balance/routes"
	bannerroutes "shopera/internal/modules/banner/routes"
	basketroutes "shopera/internal/modules/basket/routes"
	brandroutes "shopera/internal/modules/brand/routes"
	categoryroutes "shopera/internal/modules/category/routes"
	chatroutes "shopera/internal/modules/chat/routes"
	colorroutes "shopera/internal/modules/color/routes"
	deliveryroutes "shopera/internal/modules/delivery/routes"
	favoriteroutes "shopera/internal/modules/favorite/routes"
	filterroutes "shopera/internal/modules/filter/routes"
	helproutes "shopera/internal/modules/helpandpolicy/routes"
	notificationroutes "shopera/internal/modules/notification/routes"
	orderroutes "shopera/internal/modules/order/routes"
	paymentroutes "shopera/internal/modules/payment/routes"
	popuproutes "shopera/internal/modules/popup/routes"
	productroutes "shopera/internal/modules/product/routes"
	promocoderoutes "shopera/internal/modules/promocode/routes"
	reviewroutes "shopera/internal/modules/review/routes"
	rolepermissionroutes "shopera/internal/modules/rolepermission/routes"
	settingroutes "shopera/internal/modules/setting/routes"
	sizeroutes "shopera/internal/modules/size/routes"
	userroutes "shopera/internal/modules/user/routes"
)

// registerModules mounts every feature module. Each module wires itself from Deps.
func registerModules(api *gin.RouterGroup, cfg *config.Config, db *gorm.DB) {
	roles := rolepermissionrepositories.NewRoleRepository(db)
	permissionChecker := func(userID int64) (map[string]struct{}, error) {
		perms, err := roles.UserPermissions(userID)
		if err != nil {
			return nil, err
		}
		set := make(map[string]struct{}, len(perms))
		for _, p := range perms {
			set[p.Name] = struct{}{}
		}
		return set, nil
	}

	deps := module.Deps{
		API:  api,
		DB:   db,
		Cfg:  cfg,
		Auth: middleware.AuthRequired(cfg.JWT.Secret),
		Permission: func(permission string) gin.HandlerFunc {
			return middleware.RequirePermission(permissionChecker, permission)
		},
	}

	authroutes.Register(deps)
	userroutes.Register(deps)
	settingroutes.Register(deps)
	productroutes.Register(deps)
	categoryroutes.Register(deps)
	brandroutes.Register(deps)
	colorroutes.Register(deps)
	sizeroutes.Register(deps)
	bannerroutes.Register(deps)
	popuproutes.Register(deps)
	helproutes.Register(deps)
	reviewroutes.Register(deps)
	filterroutes.Register(deps)
	basketroutes.Register(deps)
	favoriteroutes.Register(deps)
	paymentroutes.Register(deps)
	deliveryroutes.Register(deps)
	addressroutes.Register(deps)
	orderroutes.Register(deps)
	promocoderoutes.Register(deps)
	balanceroutes.Register(deps)
	notificationroutes.Register(deps)
	chatroutes.Register(deps)
	rolepermissionroutes.Register(deps)
}
