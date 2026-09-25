// Package responses holds Payment module API response shapes.
package responses

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/modules/payment/models"
)

// ProviderPublicJSON maps an active provider to its public shape.
func ProviderPublicJSON(p models.PaymentProvider) gin.H {
	return gin.H{"key": p.Key, "name": p.Name}
}

// ProviderAdminJSON maps a provider to its admin shape (config included).
func ProviderAdminJSON(p models.PaymentProvider) gin.H {
	return gin.H{
		"id":         p.ID,
		"key":        p.Key,
		"name":       p.Name,
		"is_active":  p.IsActive,
		"sort_order": p.SortOrder,
		"config":     p.Config,
	}
}
