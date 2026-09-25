// Package responses holds Order module API response shapes.
package responses

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/modules/order/models"
	productresponses "shopera/internal/modules/product/responses"
)

func latestStatus(order models.Order) gin.H {
	if len(order.Statuses) == 0 {
		return nil
	}
	latest := order.Statuses[0]
	for _, s := range order.Statuses {
		if s.ID > latest.ID {
			latest = s
		}
	}
	return gin.H{
		"id":              latest.ID,
		"status":          latest.Value().Label(),
		"status_key":      int(latest.Value()),
		"status_key_enum": latest.Value().Key(),
		"created_at":      latest.CreatedAt,
	}
}

func itemJSON(item models.OrderItem, lang string) gin.H {
	var color gin.H
	if item.Color != nil {
		color = gin.H{"id": item.Color.ID, "name": item.Color.Name, "hex": item.Color.Hex}
	}
	var size gin.H
	if item.Size != nil {
		size = gin.H{"id": item.Size.ID, "name": item.Size.Name}
	}
	var product any
	if item.Product != nil {
		product = productresponses.JSON(*item.Product, lang, nil)
	}
	return gin.H{
		"id":          item.ID,
		"quantity":    item.Quantity,
		"unit_price":  item.UnitPrice,
		"total_price": item.TotalPrice,
		"color":       color,
		"size":        size,
		"product":     product,
	}
}

func addressJSON(order models.Order) any {
	if order.Address == nil {
		return nil
	}
	a := order.Address
	return gin.H{
		"id":                     a.ID,
		"city":                   a.City,
		"town_village_district":  a.TownVillageDistrict,
		"street_building_number": a.StreetBuildingNumber,
		"unit_floor_apartment":   a.UnitFloorApartment,
		"full_name":              a.FullName,
		"contact_number":         a.ContactNumber,
	}
}

// JSON maps an order to its list shape.
func JSON(order models.Order, lang string) gin.H {
	items := make([]gin.H, 0, len(order.Items))
	for _, it := range order.Items {
		items = append(items, itemJSON(it, lang))
	}
	return gin.H{
		"id":             order.ID,
		"transaction_id": order.TransactionID,
		"total_price":    order.TotalPrice,
		"discount_price": order.DiscountPrice,
		"shipping_price": order.ShippingPrice,
		"paid_at":        order.PaidAt,
		"address_type":   order.AddressType,
		"note":           order.Note,
		"payment_type":   order.PaymentType,
		"pricing_type":   pricingType(order),
		"created_at":     order.CreatedAt,
		"latest_status":  latestStatus(order),
		"address":        addressJSON(order),
		"items":          items,
	}
}

// DetailJSON maps an order to its detail shape (adds status history).
func DetailJSON(order models.Order, lang string) gin.H {
	out := JSON(order, lang)
	out["user_id"] = order.UserID
	out["address_id"] = order.AddressID
	out["updated_at"] = order.UpdatedAt
	out["deleted_at"] = order.DeletedAt

	statuses := make([]gin.H, 0, len(order.Statuses))
	for _, s := range order.Statuses {
		statuses = append(statuses, gin.H{
			"id":         s.ID,
			"status":     s.Value().Label(),
			"status_key": int(s.Value()),
			"created_at": s.CreatedAt,
		})
	}
	out["statuses"] = statuses
	return out
}

// Collection maps orders to their list shape.
func Collection(orders []models.Order, lang string) []gin.H {
	out := make([]gin.H, 0, len(orders))
	for _, o := range orders {
		out = append(out, JSON(o, lang))
	}
	return out
}

func pricingType(order models.Order) string {
	if order.PricingType != nil && *order.PricingType != "" {
		return *order.PricingType
	}
	return "retail"
}
