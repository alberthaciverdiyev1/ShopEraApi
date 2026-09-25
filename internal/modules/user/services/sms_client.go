// Package services holds User module services (OTP, password reset, SMS, mail).
package services

import (
	"crypto/md5"
	"encoding/hex"
	"net/http"
	"os"
	"strings"
	"time"
)

// SmsClient delivers an SMS message.
type SmsClient interface {
	SendSms(msisdn, text string) error
}

// LsimClient is the Lsim.az QuickSMS gateway (Laravel OtpService karşılığı).
type LsimClient struct {
	Login    string
	Password string
	Sender   string
	url      string
}

// NewLsimClient builds the client from OTP_* environment variables.
func NewLsimClient() *LsimClient {
	return &LsimClient{
		Login:    os.Getenv("OTP_LOGIN"),
		Password: os.Getenv("OTP_PASSWORD"),
		Sender:   os.Getenv("OTP_SENDER"),
		url:      "https://apps.lsim.az/quicksms/v1/send",
	}
}

func (c *LsimClient) SendSms(msisdn, text string) error {
	if c.Login == "" || c.Password == "" || c.Sender == "" {
		return nil // OTP not configured; nothing to send.
	}

	msisdn = "994" + strings.TrimPrefix(msisdn, "0")
	md5Password := md5hex(c.Password)
	key := md5hex(md5Password + c.Login + text + msisdn + c.Sender)

	req, err := http.NewRequest(http.MethodGet, c.url, nil)
	if err != nil {
		return err
	}
	q := req.URL.Query()
	q.Set("login", c.Login)
	q.Set("msisdn", msisdn)
	q.Set("text", text)
	q.Set("sender", c.Sender)
	q.Set("key", key)
	q.Set("unicode", "false")
	req.URL.RawQuery = q.Encode()

	client := &http.Client{Timeout: 15 * time.Second}
	resp, err := client.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()
	return nil
}

func md5hex(value string) string {
	sum := md5.Sum([]byte(value))
	return hex.EncodeToString(sum[:])
}
