package helpers

import "golang.org/x/crypto/bcrypt"

const bcryptRounds = 12

// HashPassword hashes a password with bcrypt.
func HashPassword(plain string) (string, error) {
	hashed, err := bcrypt.GenerateFromPassword([]byte(plain), bcryptRounds)
	return string(hashed), err
}

// CheckPassword compares a plain password with a bcrypt hash.
func CheckPassword(hash, plain string) bool {
	return bcrypt.CompareHashAndPassword([]byte(hash), []byte(plain)) == nil
}
