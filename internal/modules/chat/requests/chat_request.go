// Package requests holds Chat module request payloads.
package requests

// SendRequest is the chat send payload (multipart or JSON).
type SendRequest struct {
	Message      *string `json:"message" form:"message"`
	TargetUserID *int64  `json:"target_user_id" form:"target_user_id"`
}

// AutoReplySaveRequest is the auto-reply create/update payload.
type AutoReplySaveRequest struct {
	Question map[string]string `json:"question" binding:"required"`
	Answer   map[string]string `json:"answer" binding:"required"`
}
