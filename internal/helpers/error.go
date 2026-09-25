package helpers

// AppError is a service-level error carrying an HTTP status.
type AppError struct {
	Status  int
	Message string
}

func (e *AppError) Error() string { return e.Message }

// NewAppError builds an AppError.
func NewAppError(status int, message string) *AppError {
	return &AppError{Status: status, Message: message}
}
