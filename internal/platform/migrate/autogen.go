package migrate

import (
	"gorm.io/gorm"

	addressmodels "shopera/internal/modules/address/models"
	balancemodels "shopera/internal/modules/balance/models"
	bannermodels "shopera/internal/modules/banner/models"
	basketmodels "shopera/internal/modules/basket/models"
	brandmodels "shopera/internal/modules/brand/models"
	categorymodels "shopera/internal/modules/category/models"
	chatmodels "shopera/internal/modules/chat/models"
	colormodels "shopera/internal/modules/color/models"
	deliverymodels "shopera/internal/modules/delivery/models"
	favoritemodels "shopera/internal/modules/favorite/models"
	filtermodels "shopera/internal/modules/filter/models"
	helpmodels "shopera/internal/modules/helpandpolicy/models"
	notificationmodels "shopera/internal/modules/notification/models"
	ordermodels "shopera/internal/modules/order/models"
	paymentmodels "shopera/internal/modules/payment/models"
	popupmodels "shopera/internal/modules/popup/models"
	productmodels "shopera/internal/modules/product/models"
	promocodemodels "shopera/internal/modules/promocode/models"
	reviewmodels "shopera/internal/modules/review/models"
	rolepermissionmodels "shopera/internal/modules/rolepermission/models"
	settingmodels "shopera/internal/modules/setting/models"
	sizemodels "shopera/internal/modules/size/models"
	usermodels "shopera/internal/modules/user/models"
)

// AllModels lists every table entity the app defines (order matters: parents first).
func AllModels() []any {
	return []any{
		&usermodels.User{},
		&rolepermissionmodels.Role{},
		&rolepermissionmodels.Permission{},
		&rolepermissionmodels.ModelHasPermission{},
		&rolepermissionmodels.ModelHasRole{},
		&rolepermissionmodels.RoleHasPermission{},
		&PersonalAccessToken{},
		&usermodels.OtpEmail{},
		&usermodels.PasswordResetRequest{},
		&categorymodels.Category{},
		&brandmodels.Brand{},
		&colormodels.Color{},
		&sizemodels.Size{},
		&settingmodels.Setting{},
		&productmodels.Product{},
		&productmodels.ProductImage{},
		&productmodels.ProductVideo{},
		&productmodels.ProductSize{},
		&reviewmodels.Review{},
		&ProductStockSubscription{},
		&basketmodels.Basket{},
		&favoritemodels.Favorite{},
		&addressmodels.Address{},
		&deliverymodels.City{},
		&deliverymodels.CityTown{},
		&deliverymodels.DeliveryPrice{},
		&deliverymodels.PickupPoint{},
		&deliverymodels.DeliveryInfo{},
		&ordermodels.Order{},
		&ordermodels.OrderItem{},
		&ordermodels.OrderStatusRow{},
		&promocodemodels.PromoCode{},
		&promocodemodels.UsedPromoCode{},
		&paymentmodels.PaymentProvider{},
		&balancemodels.Balance{},
		&notificationmodels.Notification{},
		&notificationmodels.NotificationToken{},
		&NotificationUser{},
		&chatmodels.Conversation{},
		&chatmodels.Message{},
		&chatmodels.MessageAttachment{},
		&chatmodels.AutoReply{},
		&bannermodels.Banner{},
		&popupmodels.Popup{},
		&helpmodels.Faq{},
		&helpmodels.LegalTerm{},
		&filtermodels.Filter{},
		&filtermodels.CategoryFilter{},
		&filtermodels.ProductFilter{},
	}
}

// AutoMigrate creates the full schema from the entities (fresh DB only).
// NOTE: this is a bootstrap, not the production migration mechanism — see Run().
func AutoMigrate(db *gorm.DB) error {
	return db.AutoMigrate(AllModels()...)
}
