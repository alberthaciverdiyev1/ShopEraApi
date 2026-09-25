package middleware

import (
	"net/http"

	"github.com/gin-gonic/gin"
)

// CORS allows cross-origin requests. When origins is empty (or contains "*")
// every origin is allowed; otherwise only the listed origins are echoed back.
func CORS(origins []string) gin.HandlerFunc {
	allowAll := len(origins) == 0
	allowed := map[string]bool{}
	for _, origin := range origins {
		if origin == "*" {
			allowAll = true
		}
		allowed[origin] = true
	}

	return func(c *gin.Context) {
		origin := c.GetHeader("Origin")
		switch {
		case allowAll && origin != "":
			c.Header("Access-Control-Allow-Origin", origin)
			c.Header("Vary", "Origin")
			c.Header("Access-Control-Allow-Credentials", "true")
		case allowAll:
			c.Header("Access-Control-Allow-Origin", "*")
		case allowed[origin]:
			c.Header("Access-Control-Allow-Origin", origin)
			c.Header("Vary", "Origin")
			c.Header("Access-Control-Allow-Credentials", "true")
		}

		if c.GetHeader("Access-Control-Allow-Origin") != "" {
			c.Header("Access-Control-Allow-Headers", "Authorization, Content-Type, Accept, Accept-Language, X-Requested-With, Origin")
			c.Header("Access-Control-Allow-Methods", "GET, POST, PUT, PATCH, DELETE, OPTIONS")
			c.Header("Access-Control-Max-Age", "86400")
		}

		if c.Request.Method == http.MethodOptions {
			c.AbortWithStatus(http.StatusNoContent)
			return
		}
		c.Next()
	}
}
