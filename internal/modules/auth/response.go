package auth

// Result is the auth success payload (register/login).
type Result struct {
	Token string         `json:"token"`
	User  map[string]any `json:"user"`
}
