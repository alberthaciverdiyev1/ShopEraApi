package helpers

import (
	"regexp"

	"gorm.io/gorm"
)

var orderColumnPattern = regexp.MustCompile(`^[a-zA-Z0-9_.]+$`)

// ApplyOrder applies order_by/order_type, falling back to the given column desc
// (Laravel orderBy karşılığı). Column names are sanitized.
func (q Query) ApplyOrder(db *gorm.DB, defaultColumn string) *gorm.DB {
	column := q.OrderBy
	if column == "" || !orderColumnPattern.MatchString(column) {
		column = defaultColumn
	}

	orderType := q.OrderType
	if orderType != "asc" && orderType != "desc" {
		orderType = "desc"
	}
	return db.Order(column + " " + orderType)
}
