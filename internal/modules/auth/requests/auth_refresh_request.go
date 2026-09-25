package requests

// RefreshRequest carries the refresh token.
type RefreshRequest struct {
	RefreshToken string `json:"refresh_token" binding:"required"`
}
