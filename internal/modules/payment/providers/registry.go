package providers

// Registry holds the available providers keyed by their Key.
type Registry struct {
	items map[string]Provider
}

// NewRegistry builds the registry with the built-in providers.
func NewRegistry() *Registry {
	r := &Registry{items: map[string]Provider{}}
	r.Register(Epoint{})
	return r
}

// Register adds a provider.
func (r *Registry) Register(p Provider) { r.items[p.Key()] = p }

// Get returns a provider by key.
func (r *Registry) Get(key string) (Provider, bool) {
	p, ok := r.items[key]
	return p, ok
}
