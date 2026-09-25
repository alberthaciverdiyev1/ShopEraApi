package repositories

import (
	"errors"
	"time"

	"gorm.io/gorm"

	"shopera/internal/modules/user/models"
)

// OtpRepository is the data access for one-time codes.
type OtpRepository struct {
	db *gorm.DB
}

func NewOtpRepository(db *gorm.DB) *OtpRepository { return &OtpRepository{db: db} }

// Upsert stores (or replaces) the OTP for a key (phone/e-mail).
func (r *OtpRepository) Upsert(key string, code int, deactiveAt time.Time) error {
	var record models.OtpEmail
	err := r.db.Where("email = ?", key).First(&record).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return r.db.Create(&models.OtpEmail{Email: key, OtpCode: int16(code), DeactiveDate: deactiveAt}).Error
	}
	if err != nil {
		return err
	}
	return r.db.Model(&record).Updates(map[string]any{"otp_code": int16(code), "deactive_date": deactiveAt}).Error
}

// FindValid returns an unexpired OTP record matching the key and code.
func (r *OtpRepository) FindValid(key, code string) (*models.OtpEmail, error) {
	var record models.OtpEmail
	err := r.db.Where("email = ? AND otp_code = ? AND deactive_date > ?", key, code, time.Now()).
		First(&record).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, nil
	}
	if err != nil {
		return nil, err
	}
	return &record, nil
}

// DeleteByKey removes the OTP records for a key.
func (r *OtpRepository) DeleteByKey(key string) error {
	return r.db.Where("email = ?", key).Delete(&models.OtpEmail{}).Error
}
