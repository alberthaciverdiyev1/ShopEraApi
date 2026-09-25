package responses

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/modules/helpandpolicy/models"
)

// LegalTermJSON maps a legal term to its API shape.
func LegalTermJSON(t models.LegalTerm) gin.H {
	return gin.H{
		"id":   t.ID,
		"type": t.Type,
		"html": t.HTML,
	}
}

// LegalTermCollection maps legal terms to their API shape.
func LegalTermCollection(items []models.LegalTerm) []gin.H {
	out := make([]gin.H, 0, len(items))
	for _, t := range items {
		out = append(out, LegalTermJSON(t))
	}
	return out
}
