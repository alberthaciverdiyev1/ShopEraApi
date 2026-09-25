package middleware

import (
	"sync"
	"time"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
)

// rateLimiter is a small in-memory fixed-window limiter keyed by client + route.
type rateLimiter struct {
	mu   sync.Mutex
	hits map[string][]time.Time
}

// RateLimit allows at most max requests per window per client IP and route.
// Returns 429 with the standard envelope once the limit is exceeded.
func RateLimit(max int, window time.Duration) gin.HandlerFunc {
	rl := &rateLimiter{hits: map[string][]time.Time{}}

	return func(c *gin.Context) {
		key := c.ClientIP() + " " + c.FullPath()
		now := time.Now()

		rl.mu.Lock()
		kept := rl.hits[key][:0]
		for _, at := range rl.hits[key] {
			if now.Sub(at) < window {
				kept = append(kept, at)
			}
		}
		if len(kept) >= max {
			rl.hits[key] = kept
			rl.mu.Unlock()
			helpers.Respond(c, 429, "Too many requests. Please try again later.", nil)
			c.Abort()
			return
		}
		rl.hits[key] = append(kept, now)
		rl.mu.Unlock()

		c.Next()
	}
}
