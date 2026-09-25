package user_test

import (
	"testing"

	"shopera/internal/modules/user"
)

func TestNormalizePhone(t *testing.T) {
	cases := map[string]string{
		"994501234567":        "0501234567",
		"501234567":           "0501234567",
		"+994 (50) 123-45-67": "0501234567",
		"":                    "",
	}
	for input, want := range cases {
		if got := user.NormalizePhone(input); got != want {
			t.Errorf("NormalizePhone(%q) = %q, want %q", input, got, want)
		}
	}
}
