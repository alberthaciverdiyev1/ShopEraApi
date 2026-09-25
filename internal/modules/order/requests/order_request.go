// Package requests holds Order module request payloads.
package requests

// CreateRequest is the order-from-basket payload.
type CreateRequest struct {
	Note           *string `json:"note" form:"note"`
	PayWithBalance *bool   `json:"pay_with_balance" form:"pay_with_balance"`
	PromoCode      *string `json:"promo_code" form:"promo_code"`
	AddressType    string  `json:"address_type" form:"address_type" binding:"required"`
	AddressTypeID  *int64  `json:"address_type_id" form:"address_type_id"`
	PaymentType    *string `json:"payment_type" form:"payment_type"`
}

// BuyOneRequest is the buy-one payload.
type BuyOneRequest struct {
	Note           *string `json:"note" form:"note"`
	PayWithBalance *bool   `json:"pay_with_balance" form:"pay_with_balance"`
	PromoCode      *string `json:"promo_code" form:"promo_code"`
	SizeID         *int64  `json:"size_id" form:"size_id"`
	ColorID        *int64  `json:"color_id" form:"color_id"`
	Quantity       int     `json:"quantity" form:"quantity"`
}

// UpdateRequest changes an order's status.
type UpdateRequest struct {
	Status          string `json:"status" binding:"required"`
	ReturnToBalance *bool  `json:"return_to_balance"`
}
