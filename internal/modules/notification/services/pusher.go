// Package services holds Notification module business logic.
package services

import "context"

// Pusher delivers a push message to a device.
//
// TODO(DEFERRED): real FCM HTTP v1 implementation (service account + OAuth2).
// For now LogPusher is a no-op so the API surface is complete.
type Pusher interface {
	Send(ctx context.Context, deviceToken, title, body string, data map[string]string) error
}

// LogPusher is a placeholder pusher that does nothing.
type LogPusher struct{}

func (LogPusher) Send(context.Context, string, string, string, map[string]string) error { return nil }
