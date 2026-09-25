// Package responses holds HelpAndPolicy module API response shapes.
package responses

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	"shopera/internal/modules/helpandpolicy/models"
)

// FaqJSON maps a faq to its API shape (title/description localized to lang).
func FaqJSON(f models.Faq, lang string) gin.H {
	return gin.H{
		"id":          f.ID,
		"title":       helpers.Trans(f.Title, lang),
		"description": helpers.Trans(f.Description, lang),
		"type":        f.Type,
	}
}

// FaqCollection maps faqs to their API shape.
func FaqCollection(items []models.Faq, lang string) []gin.H {
	out := make([]gin.H, 0, len(items))
	for _, f := range items {
		out = append(out, FaqJSON(f, lang))
	}
	return out
}
