// Package requests holds User module request payloads (validated by binding tags).
package requests

// ChangeEmailRequest is the PUT /user/change-email payload.
type ChangeEmailRequest struct {
	Email  string `json:"email" binding:"required,email"`
	UserID *int64 `json:"user_id"`
}

// ChangeNameRequest is the PUT /user/change-name payload.
type ChangeNameRequest struct {
	Name   string `json:"name" binding:"required,max=255"`
	UserID *int64 `json:"user_id"`
}

// ChangeSurnameRequest is the PUT /user/change-surname payload.
type ChangeSurnameRequest struct {
	Surname string `json:"surname" binding:"required,max=255"`
	UserID  *int64 `json:"user_id"`
}

// ChangePhoneRequest is the PUT /user/change-phone payload (self only, OTP).
type ChangePhoneRequest struct {
	Phone   string `json:"phone" binding:"required,max=255"`
	OtpCode string `json:"otpCode" binding:"required,len=4"`
}

// BlockUserRequest is the POST /user/block payload. Block is a pointer so a
// false value is distinguishable from "omitted".
type BlockUserRequest struct {
	UserID int64 `json:"user_id" binding:"required"`
	Block  *bool `json:"block"`
}

// WholesalerStatusRequest is the PUT /user/wholesaler-status payload.
type WholesalerStatusRequest struct {
	UserID       int64 `json:"user_id" binding:"required"`
	IsWholesaler *bool `json:"is_wholesaler"`
}
