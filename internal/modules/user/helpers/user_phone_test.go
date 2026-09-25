package helpers_test

import (
	"testing"

	userhelpers "shopera/internal/modules/user/helpers"
)

func TestNormalizePhone(t *testing.T) {
	cases := map[string]string{
		"994501234567":        "0501234567",
		"501234567":           "0501234567",
		"+994 (50) 123-45-67": "0501234567",
		"":                    "",
	}
	for input, want := range cases {
		if got := userhelpers.NormalizePhone(input); got != want {
			t.Errorf("NormalizePhone(%q) = %q, want %q", input, got, want)
		}
	}
}
