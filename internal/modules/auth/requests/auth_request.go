// Package requests holds Auth module request payloads (validated by binding tags).
package requests

// RegisterRequest is the register payload.
type RegisterRequest struct {
	Name     string  `json:"name" binding:"required,max=255"`
	Surname  *string `json:"surname"`
	Email    *string `json:"email" binding:"omitempty,email"`
	Password string  `json:"password" binding:"required,min=6"`
	Phone    string  `json:"phone" binding:"required,max=20"`
	OtpCode  *string `json:"otpCode"`
}

// LoginRequest is the login payload.
type LoginRequest struct {
	Phone    string `json:"phone" binding:"required"`
	Password string `json:"password" binding:"required,min=6"`
}
