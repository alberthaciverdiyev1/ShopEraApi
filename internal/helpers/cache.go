package helpers

import (
	"sync"
	"time"
)

// cacheEntry is one cached value with its expiry.
type cacheEntry struct {
	value   any
	expires time.Time
}

// memCache is a tiny in-process TTL cache (Laravel Cache::remember karşılığı).
// It is per-process; with multiple instances each keeps its own copy.
type memCache struct {
	mu    sync.RWMutex
	items map[string]cacheEntry
}

var cache = &memCache{items: map[string]cacheEntry{}}

// CacheGet returns a cached value when present and unexpired.
func CacheGet(key string) (any, bool) {
	cache.mu.RLock()
	entry, ok := cache.items[key]
	cache.mu.RUnlock()
	if !ok {
		return nil, false
	}
	if time.Now().After(entry.expires) {
		CacheForget(key)
		return nil, false
	}
	return entry.value, true
}

// CacheSet stores a value under a key for ttl.
func CacheSet(key string, value any, ttl time.Duration) {
	cache.mu.Lock()
	cache.items[key] = cacheEntry{value: value, expires: time.Now().Add(ttl)}
	cache.mu.Unlock()
}

// CacheForget removes a cached value.
func CacheForget(key string) {
	cache.mu.Lock()
	delete(cache.items, key)
	cache.mu.Unlock()
}

// CacheRemember returns the cached value for key, computing it with fn on a miss.
func CacheRemember[T any](key string, ttl time.Duration, fn func() (T, error)) (T, error) {
	if value, ok := CacheGet(key); ok {
		if typed, ok := value.(T); ok {
			return typed, nil
		}
	}

	result, err := fn()
	if err != nil {
		var zero T
		return zero, err
	}
	CacheSet(key, result, ttl)
	return result, nil
}
