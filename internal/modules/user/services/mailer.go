package services

// Mailer sends a plain e-mail message.
type Mailer interface {
	Send(to, subject, body string) error
}

// LogMailer is a placeholder (no SMTP configured yet).
type LogMailer struct{}

func (LogMailer) Send(string, string, string) error { return nil }
