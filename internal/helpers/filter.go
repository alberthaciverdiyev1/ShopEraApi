package helpers

import (
	"strings"

	"gorm.io/gorm"
)

// Where applies an exact-match filter: ?<param>=value.
func (q Query) Where(db *gorm.DB, column, param string) *gorm.DB {
	if v, ok := q.Params[param]; ok && v != "" {
		return db.Where(column+" = ?", v)
	}
	return db
}

// WhereIn applies an IN filter: ?<param>=1,2,3 (or repeated/array params).
func (q Query) WhereIn(db *gorm.DB, column, param string) *gorm.DB {
	values := q.list(param)
	if len(values) == 0 {
		return db
	}
	return db.Where(column+" IN ?", values)
}

// WhereNotIn applies a NOT IN filter: ?<param>=1,2,3.
func (q Query) WhereNotIn(db *gorm.DB, column, param string) *gorm.DB {
	values := q.list(param)
	if len(values) == 0 {
		return db
	}
	return db.Where(column+" NOT IN ?", values)
}

// WhereNull applies IS NULL when the param is "1"/"true", IS NOT NULL when "0"/"false".
func (q Query) WhereNull(db *gorm.DB, column, param string) *gorm.DB {
	v, ok := q.Params[param]
	if !ok || v == "" {
		return db
	}
	if v == "1" || v == "true" {
		return db.Where(column + " IS NULL")
	}
	return db.Where(column + " IS NOT NULL")
}

// list splits a comma-separated param (or a single value) into trimmed values.
func (q Query) list(param string) []string {
	raw, ok := q.Params[param]
	if !ok || strings.TrimSpace(raw) == "" {
		return nil
	}
	parts := strings.Split(raw, ",")
	values := make([]string, 0, len(parts))
	for _, p := range parts {
		if p = strings.TrimSpace(p); p != "" {
			values = append(values, p)
		}
	}
	return values
}

// ApplyWhereEach applies an exact-match filter for every column that has a
// non-empty param with the same name (Laravel whereEach karşılığı).
func (q Query) ApplyWhereEach(db *gorm.DB, columns ...string) *gorm.DB {
	for _, column := range columns {
		if v, ok := q.Params[column]; ok && v != "" {
			db = db.Where(column+" = ?", v)
		}
	}
	return db
}
