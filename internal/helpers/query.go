package helpers

import (
	"strconv"

	"github.com/gin-gonic/gin"
)

// Query bundles the list/search/filter/order/pagination parameters
// (Laravel filterLike + orderBy + paginate + rangeFilter karşılığı).
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
		if len(v) > 0 {
			params[k] = v[0]
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
