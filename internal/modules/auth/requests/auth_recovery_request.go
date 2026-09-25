// Package requests holds Auth module request payloads (validated by binding tags).
package requests

// SendOtpRequest is the POST /auth/send-otp payload.
type SendOtpRequest struct {
	Phone string `json:"phone" binding:"required"`
}

// CheckOtpRequest is the POST /auth/check-otp payload.
type CheckOtpRequest struct {
	Phone string `json:"phone" binding:"required"`
	Otp   string `json:"otp" binding:"required,len=4"`
}

// ResetPasswordRequest is the POST /auth/reset-password (phone OTP) payload.
type ResetPasswordRequest struct {
	Phone                string `json:"phone" binding:"required"`
	OtpCode              string `json:"otpCode" binding:"required,len=4"`
	Password             string `json:"password" binding:"required,min=6"`
	PasswordConfirmation string `json:"password_confirmation"`
}

// SendPasswordResetEmailRequest is the POST /auth/password/email-code payload.
type SendPasswordResetEmailRequest struct {
	Email string `json:"email" binding:"required,email,max=190"`
}

// ResetPasswordByEmailRequest is the POST /auth/password/email-reset payload.
type ResetPasswordByEmailRequest struct {
	Email                string `json:"email" binding:"required,email,max=190"`
	OtpCode              string `json:"otpCode" binding:"required,len=4"`
	Password             string `json:"password" binding:"required,min=6"`
	PasswordConfirmation string `json:"password_confirmation"`
}

// ChangePasswordRequest is the POST /auth/change-password payload.
type ChangePasswordRequest struct {
	CurrentPassword         string `json:"current_password" binding:"required"`
	NewPassword             string `json:"new_password" binding:"required,min=6"`
	NewPasswordConfirmation string `json:"new_password_confirmation"`
}

// AdminChangePasswordRequest is the POST /auth/admin-change-password payload.
type AdminChangePasswordRequest struct {
	UserID                  int64  `json:"user_id" binding:"required"`
	NewPassword             string `json:"new_password" binding:"required,min=6"`
	NewPasswordConfirmation string `json:"new_password_confirmation"`
}

// CreateResetRequestPayload is the POST /auth/password-reset-requests payload.
type CreateResetRequestPayload struct {
	Phone string  `json:"phone" binding:"required,max=32"`
	Note  *string `json:"note"`
}

// ResolveResetRequestPayload is the PUT /auth/password-reset-requests/:id/resolve payload.
type ResolveResetRequestPayload struct {
	Password             string `json:"password" binding:"required,min=6"`
	PasswordConfirmation string `json:"password_confirmation"`
}
