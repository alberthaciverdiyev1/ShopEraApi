// Package services holds Order module business logic.
package services

import (
	"crypto/rand"
	"encoding/hex"
	"strings"
	"time"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	addressrepositories "shopera/internal/modules/address/repositories"
	balancemodels "shopera/internal/modules/balance/models"
	balancerepositories "shopera/internal/modules/balance/repositories"
	basketrepositories "shopera/internal/modules/basket/repositories"
	deliveryrepositories "shopera/internal/modules/delivery/repositories"
	"shopera/internal/modules/order/models"
	orderrepositories "shopera/internal/modules/order/repositories"
	orderrequests "shopera/internal/modules/order/requests"
	orderresponses "shopera/internal/modules/order/responses"
	producthelpers "shopera/internal/modules/product/helpers"
	productrepositories "shopera/internal/modules/product/repositories"
	promocodemodels "shopera/internal/modules/promocode/models"
	promocoderepositories "shopera/internal/modules/promocode/repositories"
	promocodeservices "shopera/internal/modules/promocode/services"
)

// OrderService holds the order business logic.
type OrderService struct {
	orders    *orderrepositories.OrderRepository
	baskets   *basketrepositories.BasketRepository
	products  *productrepositories.ProductRepository
	addresses *addressrepositories.AddressRepository
	cities    *deliveryrepositories.CityRepository
	delivery  *deliveryrepositories.DeliveryPriceRepository
	pickups   *deliveryrepositories.PickupPointRepository
	promos    *promocodeservices.PromoCodeService
	promoRepo *promocoderepositories.PromoCodeRepository
	balances  *balancerepositories.BalanceRepository
}

func NewOrderService(
	orders *orderrepositories.OrderRepository,
	baskets *basketrepositories.BasketRepository,
	products *productrepositories.ProductRepository,
	addresses *addressrepositories.AddressRepository,
	cities *deliveryrepositories.CityRepository,
	delivery *deliveryrepositories.DeliveryPriceRepository,
	pickups *deliveryrepositories.PickupPointRepository,
	promos *promocodeservices.PromoCodeService,
	promoRepo *promocoderepositories.PromoCodeRepository,
	balances *balancerepositories.BalanceRepository,
) *OrderService {
	return &OrderService{orders: orders, baskets: baskets, products: products, addresses: addresses, cities: cities, delivery: delivery, pickups: pickups, promos: promos, promoRepo: promoRepo, balances: balances}
}

// List returns the user's orders.
func (s *OrderService) List(userID int64, q helpers.Query, lang string) (gin.H, error) {
	items, total, err := s.orders.ListForUser(userID, q)
	if err != nil {
		return nil, err
	}
	return gin.H{"data": orderresponses.Collection(items, lang), "meta": q.Meta(total)}, nil
}

// AdminList returns every order.
func (s *OrderService) AdminList(q helpers.Query, lang string) (gin.H, error) {
	items, total, err := s.orders.ListAll(q)
	if err != nil {
		return nil, err
	}
	return gin.H{"data": orderresponses.Collection(items, lang), "meta": q.Meta(total)}, nil
}

// Completed returns the user's delivered orders.
func (s *OrderService) Completed(userID int64, q helpers.Query, lang string) (gin.H, error) {
	items, total, err := s.orders.ListForUser(userID, q)
	if err != nil {
		return nil, err
	}
	completed := make([]models.Order, 0, len(items))
	for _, o := range items {
		if latestStatusValue(o) == models.StatusDelivered {
			completed = append(completed, o)
		}
	}
	return gin.H{"data": orderresponses.Collection(completed, lang), "meta": q.Meta(total)}, nil
}

// Details returns the user's order detail.
func (s *OrderService) Details(userID, id int64, lang string) (gin.H, error) {
	order, err := s.orders.FindForUser(userID, id)
	if err != nil || order == nil {
		return nil, err
	}
	return orderresponses.DetailJSON(*order, lang), nil
}

// DetailsAdmin returns any order detail.
func (s *OrderService) DetailsAdmin(id int64, lang string) (gin.H, error) {
	order, err := s.orders.FindByID(id)
	if err != nil || order == nil {
		return nil, err
	}
	return orderresponses.DetailJSON(*order, lang), nil
}

// Update changes an order's status.
func (s *OrderService) Update(id int64, req orderrequests.UpdateRequest, lang string) (gin.H, error) {
	status, ok := resolveStatus(req.Status)
	if !ok {
		return nil, helpers.NewAppError(422, "Invalid order status.")
	}
	if _, err := s.orders.FindByID(id); err != nil {
		return nil, err
	}
	order, err := s.orders.AddStatus(id, status)
	if err != nil {
		return nil, err
	}
	return orderresponses.DetailJSON(*order, lang), nil
}

// Delete removes an order.
func (s *OrderService) Delete(id int64) error {
	return s.orders.Delete(id)
}

// CalculateDeliveryPrice returns the shipping price for an address choice.
func (s *OrderService) CalculateDeliveryPrice(userID int64, addressType string, addressTypeID *int64) (float64, error) {
	return s.shippingPrice(userID, addressType, addressTypeID, s.basketSubtotal(userID))
}

// PreviewOrder computes totals without creating the order.
func (s *OrderService) PreviewOrder(userID int64, addressType string, addressTypeID *int64, lang string) (gin.H, error) {
	items, subtotal, err := s.basketItemsForOrder(userID)
	if err != nil {
		return nil, err
	}
	shipping, err := s.shippingPrice(userID, addressType, addressTypeID, subtotal)
	if err != nil {
		return nil, err
	}
	total := subtotal + shipping
	return gin.H{
		"items":          itemsJSON(items, lang),
		"subtotal":       round2(subtotal),
		"shipping_price": round2(shipping),
		"discount_price": 0,
		"total_price":    round2(total),
	}, nil
}

// OrderFromBasket creates an order from the user's basket.
func (s *OrderService) OrderFromBasket(userID int64, req orderrequests.CreateRequest, lang string) (gin.H, error) {
	items, subtotal, err := s.basketItemsForOrder(userID)
	if err != nil {
		return nil, err
	}
	if len(items) == 0 {
		return nil, helpers.NewAppError(422, "Basket is empty.")
	}

	shipping, err := s.shippingPrice(userID, req.AddressType, req.AddressTypeID, subtotal)
	if err != nil {
		return nil, err
	}

	discount, promo, err := s.applyPromo(userID, req.PromoCode, subtotal)
	if err != nil {
		return nil, err
	}
	total := subtotal + shipping - discount
	if total < 0 {
		total = 0
	}

	status, paidAt, err := s.paymentStatus(userID, req.PayWithBalance, total)
	if err != nil {
		return nil, err
	}

	order := &models.Order{
		UserID:        userID,
		AddressID:     s.addressIDFor(userID, req.AddressType, req.AddressTypeID),
		TransactionID: generateTransactionID(),
		TotalPrice:    ptr(round2(total)),
		DiscountPrice: ptr(round2(discount)),
		ShippingPrice: ptr(round2(shipping)),
		PaidAt:        paidAt,
		Note:          req.Note,
		AddressType:   &req.AddressType,
		AddressTypeID: req.AddressTypeID,
		PaymentType:   req.PaymentType,
		PricingType:   strPtr("retail"),
	}
	if err := s.orders.Create(order, items, status); err != nil {
		return nil, err
	}
	s.markBasketOrdered(userID)

	if promo != nil {
		_ = s.promoRepo.MarkUsed(promo.ID, userID, &order.ID, &order.TransactionID)
		_ = s.promoRepo.DecrementUserCount(promo.ID)
	}
	if paidAt != nil {
		if err := s.payWithBalance(userID, order); err != nil {
			return nil, err
		}
	}

	created, err := s.orders.FindByID(order.ID)
	if err != nil || created == nil {
		return nil, err
	}
	return orderresponses.JSON(*created, lang), nil
}

// applyPromo validates and prices a promo code (discount on the product subtotal).
func (s *OrderService) applyPromo(userID int64, code *string, subtotal float64) (float64, *promocodemodels.PromoCode, error) {
	if code == nil || *code == "" {
		return 0, nil, nil
	}
	promo, discount, err := s.promos.ValidDiscount(userID, *code, subtotal)
	if err != nil {
		return 0, nil, err
	}
	return discount, promo, nil
}

// paymentStatus decides the initial order status; balance payment marks it paid.
func (s *OrderService) paymentStatus(userID int64, payWithBalance *bool, total float64) (models.OrderStatus, *time.Time, error) {
	if payWithBalance != nil && *payWithBalance {
		balance, err := s.balances.Sum(userID)
		if err != nil {
			return 0, nil, err
		}
		if balance < total {
			return 0, nil, helpers.NewAppError(403, "Insufficient balance to complete the order.")
		}
		now := time.Now()
		return models.StatusPlaced, &now, nil
	}
	return models.StatusWaitingPayment, nil, nil
}

// payWithBalance records the balance withdrawal for a balance-paid order.
func (s *OrderService) payWithBalance(userID int64, order *models.Order) error {
	note := "Sifariş ödənişi - " + order.TransactionID
	amount := 0.0
	if order.TotalPrice != nil {
		amount = *order.TotalPrice
	}
	return s.balances.Create(&balancemodels.Balance{
		UserID: userID, Type: balancemodels.TypeWithdrawal, Amount: &amount, Note: &note,
	})
}

// BuyOne creates an order for a single product.
func (s *OrderService) BuyOne(userID, productID int64, req orderrequests.BuyOneRequest, lang string) (gin.H, error) {
	product, err := s.products.FindByID(productID)
	if err != nil {
		return nil, err
	}
	if product == nil || !product.IsActive {
		return nil, helpers.NewAppError(403, "Product not found.")
	}

	quantity := req.Quantity
	if quantity < 1 {
		quantity = 1
	}
	if quantity > product.StockCount {
		return nil, helpers.NewAppError(403, "Only the available stock can be ordered.")
	}

	pivots, err := s.products.SizePivots([]int64{productID})
	if err != nil {
		return nil, err
	}
	unit := producthelpers.Retail(*product, pivots[productID], req.SizeID).Final
	lineTotal := unit * float64(quantity)

	order := &models.Order{
		UserID:        userID,
		TransactionID: generateTransactionID(),
		TotalPrice:    ptr(round2(lineTotal)),
		DiscountPrice: ptr(0),
		ShippingPrice: ptr(0),
		Note:          req.Note,
		PricingType:   strPtr("retail"),
	}
	item := models.OrderItem{ProductID: productID, ColorID: req.ColorID, SizeID: req.SizeID, Quantity: quantity, UnitPrice: ptr(unit), TotalPrice: ptr(lineTotal)}
	if err := s.orders.Create(order, []models.OrderItem{item}, models.StatusWaitingPayment); err != nil {
		return nil, err
	}
	created, err := s.orders.FindByID(order.ID)
	if err != nil || created == nil {
		return nil, err
	}
	return orderresponses.JSON(*created, lang), nil
}

// basketItemsForOrder maps the user's open basket to order items and a subtotal.
func (s *OrderService) basketItemsForOrder(userID int64) ([]models.OrderItem, float64, error) {
	basket, err := s.baskets.List(userID, helpers.Query{PerPage: 500})
	if err != nil {
		return nil, 0, err
	}
	items := make([]models.OrderItem, 0, len(basket))
	subtotal := 0.0
	for _, b := range basket {
		if b.Product == nil {
			continue
		}
		pivots, err := s.products.SizePivots([]int64{b.ProductID})
		if err != nil {
			return nil, 0, err
		}
		unit := producthelpers.Retail(*b.Product, pivots[b.ProductID], b.SizeID).Final
		lineTotal := unit * float64(b.Quantity)
		subtotal += lineTotal
		items = append(items, models.OrderItem{
			ProductID: b.ProductID, ColorID: b.ColorID, SizeID: b.SizeID,
			Quantity: b.Quantity, UnitPrice: ptr(unit), TotalPrice: ptr(lineTotal),
		})
	}
	return items, subtotal, nil
}

func (s *OrderService) basketSubtotal(userID int64) float64 {
	_, subtotal, err := s.basketItemsForOrder(userID)
	if err != nil {
		return 0
	}
	return subtotal
}

func (s *OrderService) markBasketOrdered(userID int64) {
	basket, err := s.baskets.List(userID, helpers.Query{PerPage: 500})
	if err != nil {
		return
	}
	for _, b := range basket {
		_ = s.baskets.Update(b.ID, map[string]any{"is_ordered": true})
	}
}

func (s *OrderService) shippingPrice(userID int64, addressType string, addressTypeID *int64, subtotal float64) (float64, error) {
	switch strings.ToUpper(addressType) {
	case "STANDARD", "STANDARD_FAST":
		if addressTypeID == nil {
			return 0, helpers.NewAppError(422, "Address is required for standard delivery.")
		}
		address, err := s.addresses.FindOwned(userID, *addressTypeID)
		if err != nil {
			return 0, err
		}
		if address == nil {
			return 0, helpers.NewAppError(403, "Address not found.")
		}
		delivery, err := s.delivery.FindActiveByCityKey(address.City)
		if err != nil {
			return 0, err
		}
		if delivery == nil {
			return 0, helpers.NewAppError(403, "Delivery is not available for your city.")
		}
		if delivery.FreeFrom != nil && subtotal >= *delivery.FreeFrom {
			return 0, nil
		}
		if strings.ToUpper(addressType) == "STANDARD_FAST" && delivery.FastPrice != nil {
			return *delivery.FastPrice, nil
		}
		if delivery.Price != nil {
			return *delivery.Price, nil
		}
		return 0, nil
	case "PICKUP_POINT":
		if addressTypeID == nil {
			return 0, helpers.NewAppError(422, "Pickup point is required.")
		}
		pickup, err := s.pickups.FindByID(*addressTypeID, true)
		if err != nil {
			return 0, err
		}
		if pickup == nil || pickup.Price == nil {
			return 0, helpers.NewAppError(403, "Pickup point not found.")
		}
		return *pickup.Price, nil
	case "TAKE_FROM_STORE":
		return 0, nil
	default:
		return 0, helpers.NewAppError(422, "Invalid address type.")
	}
}

func (s *OrderService) addressIDFor(userID int64, addressType string, addressTypeID *int64) *int64 {
	switch strings.ToUpper(addressType) {
	case "STANDARD", "STANDARD_FAST":
		return addressTypeID
	default:
		return nil
	}
}

func latestStatusValue(order models.Order) models.OrderStatus {
	if len(order.Statuses) == 0 {
		return models.StatusWaitingPayment
	}
	latest := order.Statuses[0]
	for _, s := range order.Statuses {
		if s.ID > latest.ID {
			latest = s
		}
	}
	return latest.Value()
}

func resolveStatus(value string) (models.OrderStatus, bool) {
	v := strings.ToUpper(strings.TrimSpace(value))
	for _, s := range models.AllStatuses() {
		if s.Key() == v {
			return s, true
		}
	}
	if strings.Contains(value, "") {
		if n, ok := helpers.ParseInt(value); ok {
			return models.OrderStatus(n), true
		}
	}
	return 0, false
}

func generateTransactionID() string {
	buf := make([]byte, 8)
	_, _ = rand.Read(buf)
	return "ORD" + strings.ToUpper(hex.EncodeToString(buf))
}

func itemsJSON(items []models.OrderItem, lang string) []gin.H {
	out := make([]gin.H, 0, len(items))
	for _, it := range items {
		out = append(out, gin.H{
			"product_id":  it.ProductID,
			"color_id":    it.ColorID,
			"size_id":     it.SizeID,
			"quantity":    it.Quantity,
			"unit_price":  it.UnitPrice,
			"total_price": it.TotalPrice,
		})
	}
	return out
}

func ptr(v float64) *float64   { return &v }
func strPtr(v string) *string  { return &v }
func round2(v float64) float64 { return float64(int64(v*100+0.5)) / 100 }
