package helpers

import (
	"strconv"
	"strings"
)

// ParseInt parses a trimmed integer; ok=false when invalid.
func ParseInt(value string) (int64, bool) {
	n, err := strconv.ParseInt(strings.TrimSpace(value), 10, 64)
	return n, err == nil
}

// ParseFloat parses a trimmed float; nil when empty/invalid.
func ParseFloat(value string) *float64 {
	value = strings.TrimSpace(value)
	if value == "" {
		return nil
	}
	f, err := strconv.ParseFloat(value, 64)
	if err != nil {
		return nil
	}
	return &f
}

// ParseIntPtr parses a trimmed integer into *int; nil when invalid.
func ParseIntPtr(value string) *int {
	if n, ok := ParseInt(value); ok {
		v := int(n)
		return &v
	}
	return nil
}

// ParseInt64Ptr parses a trimmed integer into *int64; nil when invalid.
func ParseInt64Ptr(value string) *int64 {
	if n, ok := ParseInt(value); ok {
		return &n
	}
	return nil
}

// ParseBool reads a "1"/"true" style boolean.
func ParseBool(value string) *bool {
	b := value == "1" || value == "true"
	return &b
}
