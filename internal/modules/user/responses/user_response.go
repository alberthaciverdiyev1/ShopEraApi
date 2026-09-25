// Package responses holds User module API response shapes.
package responses

import "shopera/internal/modules/user/models"

// Payload returns the API representation of a user.
func Payload(u *models.User, includePhone bool) map[string]any {
	payload := map[string]any{
		"id":    u.ID,
		"name":  u.Name,
		"email": u.Email,
	}
	if includePhone {
		payload["phone"] = u.Phone
	}
	return payload
}
