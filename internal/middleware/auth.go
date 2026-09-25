// Package middleware holds Gin middleware.
package middleware

import (
	"net/http"
	"strings"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
)

// AuthRequired validates the Bearer JWT and stores the user id in the context.
func AuthRequired(secret string) gin.HandlerFunc {
	return func(c *gin.Context) {
		token := bearer(c)
		if token == "" {
			unauthorized(c)
			return
		}

		userID, err := helpers.ParseToken(token, secret)
		if err != nil {
			unauthorized(c)
			return
		}

		c.Set("userID", userID)
		c.Next()
	}
}

func bearer(c *gin.Context) string {
	header := c.GetHeader("Authorization")
	token := strings.TrimSpace(strings.TrimPrefix(header, "Bearer "))
	if token == header {
		return ""
	}
	return token
}

func unauthorized(c *gin.Context) {
	helpers.Respond(c, http.StatusUnauthorized, "Unauthenticated", nil)
	c.Abort()
}
