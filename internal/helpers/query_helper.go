package helpers

import (
	"regexp"
	"strconv"
	"strings"

	"github.com/gin-gonic/gin"
	"gorm.io/gorm"
)

// ============================================================================
// Query helper — listeleme için search + filter + range + order + pagination.
// Laravel app/Helpers/QueryHeplers.php karşılığı (filterLike, rangeFilter,
// rangeDateFilter, orderBy, whereEach) + where/whereIn/whereNotIn/whereNull.
// ============================================================================

// Query bundles list/search/filter/order/pagination parameters.
type Query struct {
	Search    string
	Page      int
	PerPage   int
	OrderBy   string
	OrderType string
	Params    map[string]string
}

// ParseQuery reads the list parameters from the request.
func ParseQuery(c *gin.Context) Query {
	params := map[string]string{}
	for k, v := range c.Request.URL.Query() {
		key := strings.TrimSuffix(k, "[]")
		if len(v) > 0 {
			params[key] = strings.Join(v, ",")
		}
	}
	return Query{
		Search:    c.Query("search"),
		Page:      QueryInt(c, "page", 1),
		PerPage:   QueryInt(c, "per_page", 20),
		OrderBy:   c.Query("order_by"),
		OrderType: c.Query("order_type"),
		Params:    params,
	}
}

// QueryInt reads a positive integer query param, falling back when absent/invalid.
func QueryInt(c *gin.Context, key string, fallback int) int {
	if n, err := strconv.Atoi(c.Query(key)); err == nil && n > 0 {
		return n
	}
	return fallback
}

// QueryBoolPtr reads a boolean query param; nil when absent.
func QueryBoolPtr(c *gin.Context, key string) *bool {
	v, ok := c.GetQuery(key)
	if !ok || v == "" {
		return nil
	}
	b := v == "1" || v == "true"
	return &b
}

// SearchLocales are the locales searched in translatable jsonb columns.
var SearchLocales = []string{"az", "en", "ru", "tr"}

// SearchColumn describes a searchable column.
type SearchColumn struct {
	Column       string
	Translatable bool
}

// ApplySearch adds an accent-insensitive OR search across the given columns
// (Laravel filterLike). Searches shorter than 2 chars are ignored.
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

// Where applies an exact-match filter: ?<param>=value.
func (q Query) Where(db *gorm.DB, column, param string) *gorm.DB {
	if v, ok := q.Params[param]; ok && v != "" {
		return db.Where(column+" = ?", v)
	}
	return db
}

// WhereIn applies an IN filter: ?<param>=1,2,3 (or repeated/array params).
func (q Query) WhereIn(db *gorm.DB, column, param string) *gorm.DB {
	if values := q.list(param); len(values) > 0 {
		return db.Where(column+" IN ?", values)
	}
	return db
}

// WhereNotIn applies a NOT IN filter: ?<param>=1,2,3.
func (q Query) WhereNotIn(db *gorm.DB, column, param string) *gorm.DB {
	if values := q.list(param); len(values) > 0 {
		return db.Where(column+" NOT IN ?", values)
	}
	return db
}

// WhereNull applies IS NULL when the param is "1"/"true", otherwise IS NOT NULL.
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

// ApplyWhereEach applies an exact-match filter for every column that has a
// non-empty param with the same name (Laravel whereEach).
func (q Query) ApplyWhereEach(db *gorm.DB, columns ...string) *gorm.DB {
	for _, column := range columns {
		if v, ok := q.Params[column]; ok && v != "" {
			db = db.Where(column+" = ?", v)
		}
	}
	return db
}

// ApplyRange applies an exact, between, or min/max filter on a column
// (Laravel rangeFilter): column, column_min, column_max.
func (q Query) ApplyRange(db *gorm.DB, column string) *gorm.DB {
	exact := q.Params[column]
	min := q.Params[column+"_min"]
	max := q.Params[column+"_max"]

	switch {
	case exact != "" && min == "" && max == "":
		return db.Where(column+" = ?", exact)
	case min != "" && max != "":
		return db.Where(column+" BETWEEN ? AND ?", min, max)
	default:
		if min != "" {
			db = db.Where(column+" >= ?", min)
		}
		if max != "" {
			db = db.Where(column+" <= ?", max)
		}
		return db
	}
}

// ApplyRangeDate applies a between filter using column_min/column_max
// (Laravel rangeDateFilter).
func (q Query) ApplyRangeDate(db *gorm.DB, column string) *gorm.DB {
	min := q.Params[column+"_min"]
	max := q.Params[column+"_max"]

	switch {
	case min != "" && max != "":
		return db.Where(column+" BETWEEN ? AND ?", min, max)
	case min != "":
		return db.Where(column+" >= ?", min)
	case max != "":
		return db.Where(column+" <= ?", max)
	default:
		return db
	}
}

var orderColumnPattern = regexp.MustCompile(`^[a-zA-Z0-9_.]+$`)

// ApplyOrder applies order_by/order_type, falling back to the given column desc
// (Laravel orderBy). Column names are sanitized.
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
