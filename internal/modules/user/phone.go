package user

import (
	"regexp"
	"strings"
)

var nonDigits = regexp.MustCompile(`\D+`)

// NormalizePhone reduces a phone number to "0XXXXXXXXX".
func NormalizePhone(input string) string {
	digits := nonDigits.ReplaceAllString(input, "")

	switch {
	case len(digits) == 12 && strings.HasPrefix(digits, "994"):
		return "0" + digits[len(digits)-9:]
	case len(digits) == 9:
		return "0" + digits
	default:
		return digits
	}
}

// PhoneMatchSQL matches a stored phone column against the normalized value (? = arg).
const PhoneMatchSQL = `(
	CASE
		WHEN LENGTH(REGEXP_REPLACE(phone, '[^0-9]', '', 'g')) = 12
			AND REGEXP_REPLACE(phone, '[^0-9]', '', 'g') LIKE '994%'
			THEN '0' || RIGHT(REGEXP_REPLACE(phone, '[^0-9]', '', 'g'), 9)
		WHEN LENGTH(REGEXP_REPLACE(phone, '[^0-9]', '', 'g')) = 9
			THEN '0' || REGEXP_REPLACE(phone, '[^0-9]', '', 'g')
		ELSE REGEXP_REPLACE(phone, '[^0-9]', '', 'g')
	END = ?
)`
