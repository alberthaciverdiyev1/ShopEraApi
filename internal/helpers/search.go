package helpers

import (
	"strings"

	"gorm.io/gorm"
)

// SearchLocales are the locales searched in translatable jsonb columns.
var SearchLocales = []string{"az", "en", "ru", "tr"}

// SearchColumn describes a searchable column.
type SearchColumn struct {
	Column       string
	Translatable bool
}

// ApplySearch adds a case-insensitive OR search across the given columns
// (Laravel filterLike karşılığı).
func (q Query) ApplySearch(db *gorm.DB, columns ...SearchColumn) *gorm.DB {
	search := strings.TrimSpace(q.Search)
	if search == "" || len(columns) == 0 {
		return db
	}
	like := "%" + strings.ToLower(search) + "%"

	return db.Where(func(d *gorm.DB) *gorm.DB {
		tx := d.Session(&gorm.Session{NewDB: true})
		for i, col := range columns {
			if col.Translatable {
				for j, locale := range SearchLocales {
					expr := "lower(" + col.Column + "->>'" + locale + "') LIKE ?"
					if i == 0 && j == 0 {
						tx = tx.Where(expr, like)
					} else {
						tx = tx.Or(expr, like)
					}
				}
				continue
			}
			expr := "lower(" + col.Column + ") LIKE ?"
			if i == 0 {
				tx = tx.Where(expr, like)
			} else {
				tx = tx.Or(expr, like)
			}
		}
		return tx
	})
}
