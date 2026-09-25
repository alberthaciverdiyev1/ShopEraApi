// Package responses holds Auth module API response shapes.
package responses

// Result is the auth success payload (register/login).
type Result struct {
	Token string         `json:"token"`
	User  map[string]any `json:"user"`
}
