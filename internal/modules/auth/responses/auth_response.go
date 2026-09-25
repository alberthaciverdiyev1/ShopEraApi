// Package responses holds Auth module API response shapes.
package responses

// Result is the auth success payload (register/login/refresh).
type Result struct {
	Token        string         `json:"token"`
	RefreshToken string         `json:"refresh_token"`
	User         map[string]any `json:"user,omitempty"`
}
