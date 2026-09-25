package repositories

import (
	"crypto/rand"
	"crypto/sha256"
	"encoding/base64"
	"encoding/hex"
	"errors"
	"time"

	"gorm.io/gorm"

	"shopera/internal/modules/user/models"
)

// RefreshTokenRepository stores revocable refresh tokens (SHA-256 hashed).
type RefreshTokenRepository struct {
	db *gorm.DB
}

func NewRefreshTokenRepository(db *gorm.DB) *RefreshTokenRepository {
	return &RefreshTokenRepository{db: db}
}

// Issue creates a refresh token and returns its plaintext value.
func (r *RefreshTokenRepository) Issue(userID int64, ttl time.Duration) (string, error) {
	plain, err := randomToken()
	if err != nil {
		return "", err
	}
	record := models.RefreshToken{
		UserID:    userID,
		Token:     hashToken(plain),
		ExpiresAt: time.Now().Add(ttl),
	}
	if err := r.db.Create(&record).Error; err != nil {
		return "", err
	}
	return plain, nil
}

// Resolve returns the user id for a valid (not revoked, not expired) token.
func (r *RefreshTokenRepository) Resolve(plain string) (int64, error) {
	var record models.RefreshToken
	err := r.db.Where("token = ? AND revoked_at IS NULL AND expires_at > ?", hashToken(plain), time.Now()).
		First(&record).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return 0, nil
	}
	if err != nil {
		return 0, err
	}
	return record.UserID, nil
}

// Revoke marks a single token revoked.
func (r *RefreshTokenRepository) Revoke(plain string) error {
	return r.db.Model(&models.RefreshToken{}).
		Where("token = ? AND revoked_at IS NULL", hashToken(plain)).
		Update("revoked_at", gorm.Expr("NOW()")).Error
}

// RevokeAllForUser revokes every token of a user (logout everywhere / password change).
func (r *RefreshTokenRepository) RevokeAllForUser(userID int64) error {
	return r.db.Model(&models.RefreshToken{}).
		Where("user_id = ? AND revoked_at IS NULL", userID).
		Update("revoked_at", gorm.Expr("NOW()")).Error
}

func randomToken() (string, error) {
	buf := make([]byte, 32)
	if _, err := rand.Read(buf); err != nil {
		return "", err
	}
	return base64.RawURLEncoding.EncodeToString(buf), nil
}

func hashToken(plain string) string {
	sum := sha256.Sum256([]byte(plain))
	return hex.EncodeToString(sum[:])
}
