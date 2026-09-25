// Package responses holds Auth module API response shapes.
package responses

import usermodels "shopera/internal/modules/user/models"

// ResetRequestPayload renders a password-reset request with its user (admin list).
func ResetRequestPayload(r usermodels.PasswordResetRequest, u *usermodels.User) map[string]any {
	payload := map[string]any{
		"id":          r.ID,
		"user_id":     r.UserID,
		"phone":       r.Phone,
		"status":      r.Status,
		"note":        r.Note,
		"resolved_by": r.ResolvedBy,
		"resolved_at": r.ResolvedAt,
		"created_at":  r.CreatedAt,
		"updated_at":  r.UpdatedAt,
	}
	if u != nil {
		payload["user"] = map[string]any{
			"id":      u.ID,
			"name":    u.Name,
			"surname": u.Surname,
			"phone":   u.Phone,
			"email":   u.Email,
		}
	}
	return payload
}
