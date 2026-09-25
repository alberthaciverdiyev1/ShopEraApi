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

// ApplySearch adds an accent-insensitive OR search across the given columns
// (Laravel filterLike karşılığı). Searches shorter than 2 chars are ignored.
func (q Query) ApplySearch(db *gorm.DB, columns ...SearchColumn) *gorm.DB {
	search := strings.TrimSpace(q.Search)
	if len([]rune(search)) < 2 || len(columns) == 0 {
		return db
	}
	like := "%" + search + "%"

	return db.Where(func(d *gorm.DB) *gorm.DB {
		tx := d.Session(&gorm.Session{NewDB: true})
		first := true

		add := func(expr string) {
			if first {
				tx = tx.Where(expr, like)
				first = false
			} else {
				tx = tx.Or(expr, like)
			}
		}

		for _, col := range columns {
			if col.Translatable {
				for _, locale := range SearchLocales {
					add("unaccent(" + col.Column + "->>'" + locale + "') ILIKE unaccent(?)")
				}
				continue
			}
			add("unaccent(" + col.Column + "::text) ILIKE unaccent(?)")
		}
		return tx
	})
}
