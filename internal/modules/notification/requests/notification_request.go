// Package requests holds Notification module request payloads.
package requests

// TokenRequest registers a device token (guests allowed).
type TokenRequest struct {
	DeviceToken string  `json:"device_token" binding:"required"`
	DeviceType  *string `json:"device_type"`
}

// SendRequest creates and pushes a notification.
type SendRequest struct {
	Title string         `json:"title" binding:"required,max=255"`
	Body  string         `json:"body" binding:"required"`
	Icon  *string        `json:"icon"`
	URL   *string        `json:"url"`
	Data  map[string]any `json:"data"`
	Users []int64        `json:"users"`
	All   *bool          `json:"all"`
}
