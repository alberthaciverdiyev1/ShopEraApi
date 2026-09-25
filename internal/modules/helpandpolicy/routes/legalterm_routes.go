package routes

import (
	"github.com/gin-gonic/gin"

	helphandlers "shopera/internal/modules/helpandpolicy/handlers"
)

// RegisterLegalTerms mounts the legal-terms routes on the given group.
func mountLegalTerms(group *gin.RouterGroup, handler *helphandlers.LegalTermHandler, auth gin.HandlerFunc) {
	group.GET("/legal-terms", handler.List)
	group.GET("/legal-terms/admin", auth, handler.ListAdmin)
	group.PUT("/legal-terms/:type", auth, handler.Update)
}
