// Package helpers holds global, cross-module helpers (response, errors, jwt, password).
package helpers

import "github.com/gin-gonic/gin"

// Envelope is the standard API response shape.
type Envelope struct {
	Success    bool   `json:"success"`
	StatusCode int    `json:"status_code"`
	Message    string `json:"message"`
	Data       any    `json:"data"`
}

// Respond writes the standard envelope. Mobile clients (?is_application=1)
// receive the raw data instead of the envelope.
func Respond(c *gin.Context, status int, message string, data any) {
	if c.Query("is_application") == "1" {
		if data != nil {
			c.JSON(status, data)
			return
		}
		c.JSON(status, message)
		return
	}

	c.JSON(status, Envelope{
		Success:    status >= 200 && status < 300,
		StatusCode: status,
		Message:    message,
		Data:       data,
	})
}
