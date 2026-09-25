// Package providers holds the provider registry.
package providers

import (
	"shopera/internal/modules/payment/contract"
	"shopera/internal/modules/payment/providers/epoint"
)

// Registry holds the available providers keyed by their Key.
type Registry struct {
	items map[string]contract.Provider
}

// NewRegistry builds the registry with the built-in providers.
// Adding a new gateway (e.g. Odero) is one line here + its own package.
func NewRegistry() *Registry {
	r := &Registry{items: map[string]contract.Provider{}}
	r.Register(epoint.New())
	return r
}

// Register adds a provider.
func (r *Registry) Register(p contract.Provider) { r.items[p.Key()] = p }

// Get returns a provider by key.
func (r *Registry) Get(key string) (contract.Provider, bool) {
	p, ok := r.items[key]
	return p, ok
}
