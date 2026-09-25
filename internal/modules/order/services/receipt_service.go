package services

import (
	"fmt"
	"os"
	"path/filepath"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	orderhelpers "shopera/internal/modules/order/helpers"
	"shopera/internal/modules/order/models"
	producthelpers "shopera/internal/modules/product/helpers"
	promocodemodels "shopera/internal/modules/promocode/models"
)

// GetReceipt returns the receipt summary and pickup data for an order (JSON).
func (s *OrderService) GetReceipt(userID, orderID int64, lang string) (gin.H, error) {
	order, err := s.orders.FindForUser(userID, orderID)
	if err != nil {
		return nil, err
	}
	if order == nil {
		return nil, helpers.NewAppError(403, "Order not found.")
	}

	summary, pickup := s.receiptParts(order)
	promo := s.orderPromo(order, userID)

	orderSummary := gin.H{
		"order_id":                       order.ID,
		"transaction_id":                 order.TransactionID,
		"order_time":                     order.CreatedAt.Format("2006-01-02 15:04:05"),
		"items_totals":                   round2(summary.ItemsTotal + summary.Discounts),
		"items_discounts":                fmt.Sprintf("%g", summary.Discounts),
		"promo_code":                     nil,
		"promo_code_discount_percentage": nil,
		"shipping":                       summary.Shipping,
		"total":                          summary.Total,
	}
	if promo != nil {
		orderSummary["promo_code"] = promo.Code
		if promo.DiscountPercent != nil {
			orderSummary["promo_code_discount_percentage"] = *promo.DiscountPercent
		}
	}

	return gin.H{
		"order_summary": orderSummary,
		"pickup": gin.H{
			"city":      pickup.City,
			"town":      pickup.Town,
			"street":    pickup.Street,
			"apartment": pickup.Apartment,
			"phone":     pickup.Phone,
		},
	}, nil
}

// DownloadReceipt renders the order receipt PDF and returns its public link.
func (s *OrderService) DownloadReceipt(userID, orderID int64, lang string) (gin.H, error) {
	order, err := s.orders.FindForUser(userID, orderID)
	if err != nil {
		return nil, err
	}
	if order == nil {
		return nil, helpers.NewAppError(403, "Order not found.")
	}

	summary, pickup := s.receiptParts(order)

	var promo *orderhelpers.ReceiptPromo
	if p := s.orderPromo(order, userID); p != nil {
		promo = &orderhelpers.ReceiptPromo{Code: p.Code}
		if p.DiscountPercent != nil {
			promo.DiscountPercent = *p.DiscountPercent
		}
	}

	items := make([]orderhelpers.ReceiptItem, 0, len(order.Items))
	for _, item := range order.Items {
		title := ""
		if item.Product != nil {
			title = producthelpers.Trans(item.Product.Title, lang)
		}
		items = append(items, orderhelpers.ReceiptItem{
			Title:      title,
			Quantity:   item.Quantity,
			UnitPrice:  deref(item.UnitPrice),
			TotalPrice: deref(item.TotalPrice),
		})
	}

	pdfBytes, err := orderhelpers.BuildReceiptPDF(lang, summary, pickup, items, promo)
	if err != nil {
		return nil, err
	}

	filename := fmt.Sprintf("receipt_order_%s.pdf", order.TransactionID)
	dir := filepath.Join("storage", "app", "public", "receipts")
	if err := os.MkdirAll(dir, 0o755); err != nil {
		return nil, err
	}
	if err := os.WriteFile(filepath.Join(dir, filename), pdfBytes, 0o644); err != nil {
		return nil, err
	}

	return gin.H{"success": true, "file_link": helpers.StorageURL("receipts/" + filename)}, nil
}

// receiptParts builds the summary and pickup blocks shared by both endpoints.
func (s *OrderService) receiptParts(order *models.Order) (orderhelpers.ReceiptSummary, orderhelpers.ReceiptPickup) {
	itemsTotal := 0.0
	for _, item := range order.Items {
		itemsTotal += deref(item.TotalPrice)
	}
	itemsTotal = round2(itemsTotal)
	discount := round2(deref(order.DiscountPrice))

	summary := orderhelpers.ReceiptSummary{
		OrderID:       order.ID,
		TransactionID: order.TransactionID,
		OrderTime:     order.CreatedAt.Format("2006-01-02 15:04:05"),
		ItemsTotal:    round2(itemsTotal + discount),
		Discounts:     discount,
		Shipping:      deref(order.ShippingPrice),
		Total:         deref(order.TotalPrice),
	}

	pickup := orderhelpers.ReceiptPickup{}
	if order.Address != nil {
		pickup.City = order.Address.City
		pickup.Town = order.Address.TownVillageDistrict
		pickup.Street = order.Address.StreetBuildingNumber
		pickup.Apartment = order.Address.UnitFloorApartment
		pickup.Phone = order.Address.ContactNumber
	}
	if pickup.Phone == "" && order.User != nil {
		pickup.Phone = order.User.Phone
	}
	return summary, pickup
}

// orderPromo returns the promo code used on an order, if any.
func (s *OrderService) orderPromo(order *models.Order, userID int64) *promocodemodels.PromoCode {
	used, err := s.promoRepo.FindUsedByOrder(order.ID, userID)
	if err != nil || used == nil || used.PromoCodeID == 0 {
		return nil
	}
	promo, err := s.promos.Details(used.PromoCodeID)
	if err != nil {
		return nil
	}
	return promo
}

func deref(value *float64) float64 {
	if value == nil {
		return 0
	}
	return *value
}
