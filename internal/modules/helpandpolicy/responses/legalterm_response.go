package responses

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
	"shopera/internal/modules/helpandpolicy/models"
)

// LegalTermJSON maps a legal term to its API shape (html localized to lang).
func LegalTermJSON(t models.LegalTerm, lang string) gin.H {
	return gin.H{
		"id":   t.ID,
		"type": t.Type,
		"html": helpers.Trans(t.HTML, lang),
	}
}

// LegalTermCollection maps legal terms to their API shape.
func LegalTermCollection(items []models.LegalTerm, lang string) []gin.H {
	out := make([]gin.H, 0, len(items))
	for _, t := range items {
		out = append(out, LegalTermJSON(t, lang))
	}
	return out
}
