// Package responses holds HelpAndPolicy module API response shapes.
package responses

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/modules/helpandpolicy/models"
)

// FaqJSON maps a faq to its API shape.
func FaqJSON(f models.Faq) gin.H {
	return gin.H{
		"id":          f.ID,
		"title":       f.Title,
		"description": f.Description,
		"type":        f.Type,
	}
}

// FaqCollection maps faqs to their API shape.
func FaqCollection(items []models.Faq) []gin.H {
	out := make([]gin.H, 0, len(items))
	for _, f := range items {
		out = append(out, FaqJSON(f))
	}
	return out
}
