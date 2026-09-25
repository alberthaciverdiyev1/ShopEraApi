// Package requests holds Delivery module request payloads.
package requests

// DeliverySaveRequest is used for create and update of delivery prices.
type DeliverySaveRequest struct {
	CityName         string   `json:"city_name" binding:"required"`
	Price            *float64 `json:"price" binding:"required,gte=0"`
	FastPrice        *float64 `json:"fast_price" binding:"required,gte=0"`
	FreeFrom         *float64 `json:"free_from" binding:"omitempty,gte=0"`
	DeliveryTime     *string  `json:"delivery_time" binding:"omitempty,max=255"`
	FastDeliveryTime *string  `json:"fast_delivery_time" binding:"omitempty,max=255"`
	IsActive         *bool    `json:"is_active"`
}

// PickupPointSaveRequest is used for create and update of pickup points.
type PickupPointSaveRequest struct {
	Name         string            `json:"name" binding:"required,max=255"`
	Address      string            `json:"address" binding:"required"`
	Price        *float64          `json:"price" binding:"required,gte=0"`
	DeliveryTime map[string]string `json:"delivery_time"`
	IsActive     *bool             `json:"is_active"`
}

// CitySaveRequest is the city create/update payload.
type CitySaveRequest struct {
	Name string `json:"name" binding:"required,max=255"`
}

// DeliveryInfoSaveRequest updates a delivery info row.
type DeliveryInfoSaveRequest struct {
	Type        string            `json:"type"`
	Description map[string]string `json:"description"`
}
