// Package health exposes the liveness endpoint.
package health

import (
	"net/http"

	"github.com/gin-gonic/gin"
)

// Show reports that the service is up.
func Show(c *gin.Context) {
	c.JSON(http.StatusOK, gin.H{"status": "ok"})
}
