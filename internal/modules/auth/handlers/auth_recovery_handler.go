package handlers

import (
	"net/http"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	authrequests "shopera/internal/modules/auth/requests"
	authservices "shopera/internal/modules/auth/services"
)

// AuthRecoveryHandler serves OTP and password-recovery endpoints.
type AuthRecoveryHandler struct {
	service *authservices.AuthRecoveryService
}

func NewAuthRecoveryHandler(service *authservices.AuthRecoveryService) *AuthRecoveryHandler {
	return &AuthRecoveryHandler{service: service}
}

// SendOtp handles POST /auth/send-otp.
func (h *AuthRecoveryHandler) SendOtp(c *gin.Context) {
	var req authrequests.SendOtpRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}

	deactiveAt, err := h.service.SendOtp(req.Phone)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusCreated, "OTP sent successfully.", gin.H{"deactive_date": deactiveAt})
}

// CheckOtp handles POST /auth/check-otp.
func (h *AuthRecoveryHandler) CheckOtp(c *gin.Context) {
	var req authrequests.CheckOtpRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}

	if err := h.service.CheckOtp(req.Phone, req.Otp); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "OTP verified successfully.", nil)
}

// ResetPassword handles POST /auth/reset-password (phone OTP).
func (h *AuthRecoveryHandler) ResetPassword(c *gin.Context) {
	var req authrequests.ResetPasswordRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	if req.Password != req.PasswordConfirmation {
		helpers.ValidationFailed(c, errPasswordConfirmed{})
		return
	}

	result, err := h.service.ResetPasswordByPhone(req.Phone, req.OtpCode, req.Password)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Password reset successfully.", result)
}

// SendPasswordResetEmail handles POST /auth/password/email-code.
func (h *AuthRecoveryHandler) SendPasswordResetEmail(c *gin.Context) {
	var req authrequests.SendPasswordResetEmailRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}

	if err := h.service.SendPasswordResetEmail(req.Email); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "If the address belongs to an account, the code is on its way.", nil)
}

// ResetPasswordByEmail handles POST /auth/password/email-reset.
func (h *AuthRecoveryHandler) ResetPasswordByEmail(c *gin.Context) {
	var req authrequests.ResetPasswordByEmailRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	if req.Password != req.PasswordConfirmation {
		helpers.ValidationFailed(c, errPasswordConfirmed{})
		return
	}

	result, err := h.service.ResetPasswordByEmail(req.Email, req.OtpCode, req.Password)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Password reset successfully.", result)
}

// ChangePassword handles POST /auth/change-password (authenticated).
func (h *AuthRecoveryHandler) ChangePassword(c *gin.Context) {
	var req authrequests.ChangePasswordRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	if req.NewPassword != req.NewPasswordConfirmation {
		helpers.ValidationFailed(c, errPasswordConfirmed{})
		return
	}

	result, err := h.service.ChangePassword(c.GetInt64("userID"), req.CurrentPassword, req.NewPassword)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Password changed successfully.", result)
}

// AdminChangePassword handles POST /auth/admin-change-password (authenticated).
func (h *AuthRecoveryHandler) AdminChangePassword(c *gin.Context) {
	var req authrequests.AdminChangePasswordRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	if req.NewPassword != req.NewPasswordConfirmation {
		helpers.ValidationFailed(c, errPasswordConfirmed{})
		return
	}

	if err := h.service.AdminChangePassword(req.UserID, req.NewPassword); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Password changed successfully.", nil)
}

// CreateResetRequest handles POST /auth/password-reset-requests.
func (h *AuthRecoveryHandler) CreateResetRequest(c *gin.Context) {
	var req authrequests.CreateResetRequestPayload
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}

	if err := h.service.CreateResetRequest(req.Phone, req.Note); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusAccepted, "Şifrə sıfırlama istəyiniz adminə göndərildi.", nil)
}

// ListResetRequests handles GET /auth/password-reset-requests (authenticated).
func (h *AuthRecoveryHandler) ListResetRequests(c *gin.Context) {
	var status *string
	if v := c.Query("status"); v != "" {
		status = &v
	}
	perPage := helpers.QueryInt(c, "per_page", helpers.DefaultPerPage)
	if perPage > 100 {
		perPage = 100
	}
	page := helpers.QueryInt(c, "page", 1)

	data, err := h.service.ListResetRequests(status, c.Query("search"), page, perPage)
	if err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Password reset requests retrieved successfully.", data)
}

// ResolveResetRequest handles PUT /auth/password-reset-requests/:id/resolve (authenticated).
func (h *AuthRecoveryHandler) ResolveResetRequest(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Password reset request not found.", nil)
		return
	}

	var req authrequests.ResolveResetRequestPayload
	if err := c.ShouldBindJSON(&req); err != nil {
		helpers.ValidationFailed(c, err)
		return
	}
	if req.Password != req.PasswordConfirmation {
		helpers.ValidationFailed(c, errPasswordConfirmed{})
		return
	}

	if err := h.service.ResolveResetRequest(id, c.GetInt64("userID"), req.Password); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Password reset request resolved successfully.", nil)
}

// DismissResetRequest handles PUT /auth/password-reset-requests/:id/dismiss (authenticated).
func (h *AuthRecoveryHandler) DismissResetRequest(c *gin.Context) {
	id, err := helpers.PathID(c)
	if err != nil {
		helpers.Respond(c, http.StatusNotFound, "Password reset request not found.", nil)
		return
	}

	if err := h.service.DismissResetRequest(id, c.GetInt64("userID")); err != nil {
		helpers.FromError(c, err)
		return
	}
	helpers.Respond(c, http.StatusOK, "Password reset request dismissed.", nil)
}

// errPasswordConfirmed reports a password_confirmation mismatch in the same
// shape as the binding validator, so ValidationFailed can render it.
type errPasswordConfirmed struct{}

func (errPasswordConfirmed) Error() string { return "The password confirmation does not match." }
