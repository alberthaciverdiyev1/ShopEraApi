package requests

// Filter carries the list query parameters.
type Filter struct {
	Page    int
	PerPage int
	StoreID *int64
}
