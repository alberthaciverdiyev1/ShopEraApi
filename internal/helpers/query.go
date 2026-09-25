package helpers

import (
	"strconv"

	"github.com/gin-gonic/gin"
)

// QueryInt reads a positive integer query param, falling back when absent/invalid.
func QueryInt(c *gin.Context, key string, fallback int) int {
	if n, err := strconv.Atoi(c.Query(key)); err == nil && n > 0 {
		return n
	}
	return fallback
}
