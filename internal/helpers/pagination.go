package helpers

import (
	"github.com/gin-gonic/gin"
	"gorm.io/gorm"
)

// Offset returns the SQL offset for the current page.
func (q Query) Offset() int {
	if q.Page < 1 {
		return 0
	}
	return (q.Page - 1) * q.PerPage
}

// ApplyPage applies limit/offset to a query.
func (q Query) ApplyPage(db *gorm.DB) *gorm.DB {
	return db.Limit(q.PerPage).Offset(q.Offset())
}

// Meta builds a Laravel-style paginator meta block.
func (q Query) Meta(total int64) gin.H {
	lastPage := int((total + int64(q.PerPage) - 1) / int64(q.PerPage))
	return gin.H{
		"current_page": q.Page,
		"per_page":     q.PerPage,
		"total":        total,
		"last_page":    lastPage,
	}
}
