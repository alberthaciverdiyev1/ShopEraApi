package routes

import (
	"github.com/gin-gonic/gin"

	helphandlers "shopera/internal/modules/helpandpolicy/handlers"
)

// mountLegalTerms registers the legal-terms routes on the given group.
func mountLegalTerms(group *gin.RouterGroup, handler *helphandlers.LegalTermHandler, auth gin.HandlerFunc, perm func(string) gin.HandlerFunc) {
	group.GET("/legal-terms", handler.List)
	group.GET("/legal-terms/admin", auth, handler.ListAdmin)
	group.GET("/privacy-and-policy", handler.PrivacyAndPolicy)
	group.PUT("/legal-terms/:type", auth, perm("update legal-terms"), handler.Update)
}
