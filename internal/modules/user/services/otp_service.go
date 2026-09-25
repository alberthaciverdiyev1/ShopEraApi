package services

import (
	"crypto/rand"
	"math/big"
	"time"

	"shopera/internal/modules/user/repositories"
)

// OtpService issues and verifies one-time codes (SMS).
//
// NOTE: NOT wired into routes or register yet — code only (per current decision).
type OtpService struct {
	repo *repositories.OtpRepository
	sms  SmsClient
}

func NewOtpService(repo *repositories.OtpRepository, sms SmsClient) *OtpService {
	return &OtpService{repo: repo, sms: sms}
}

// SendOtp generates a 4-digit code, stores it and sends it via SMS.
func (s *OtpService) SendOtp(key, text string) (time.Time, error) {
	code := randomCode()
	deactiveAt := time.Now().Add(10 * time.Minute)
	if err := s.repo.Upsert(key, code, deactiveAt); err != nil {
		return time.Time{}, err
	}
	if err := s.sms.SendSms(key, text); err != nil {
		return time.Time{}, err
	}
	return deactiveAt, nil
}

// CheckOtp verifies a code for a key.
func (s *OtpService) CheckOtp(key, code string) (bool, error) {
	record, err := s.repo.FindValid(key, code)
	if err != nil {
		return false, err
	}
	return record != nil, nil
}

func randomCode() int {
	n, _ := rand.Int(rand.Reader, big.NewInt(9000))
	return int(n.Int64()) + 1000
}
