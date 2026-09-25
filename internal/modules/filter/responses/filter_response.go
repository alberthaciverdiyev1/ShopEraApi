// Package responses holds Filter module API response shapes.
package responses

import (
	"github.com/gin-gonic/gin"

	"shopera/internal/modules/filter/models"
)

// JSON maps a filter to its API shape (with the values found in the category).
func JSON(f models.Filter, values []string) gin.H {
	if values == nil {
		values = []string{}
	}
	return gin.H{
		"id":      f.ID,
		"title":   f.Title,
		"type":    f.Type,
		"options": f.Options,
		"values":  values,
	}
}
