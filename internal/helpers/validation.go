package helpers

import (
	"errors"
	"net/http"

	"github.com/gin-gonic/gin"
)

// ValidationFailed writes a 422 envelope for a binding error.
func ValidationFailed(c *gin.Context, err error) {
	Respond(c, http.StatusUnprocessableEntity, "Validation failed", gin.H{"errors": []string{err.Error()}})
}

// FromError maps an AppError (or any error) to an HTTP response.
func FromError(c *gin.Context, err error) {
	var appErr *AppError
	if errors.As(err, &appErr) {
		Respond(c, appErr.Status, appErr.Message, nil)
		return
	}
	Respond(c, http.StatusInternalServerError, "Server Error", nil)
}
