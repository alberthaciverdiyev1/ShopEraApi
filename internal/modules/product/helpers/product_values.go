package helpers

import "time"

// GenderValue maps an input string to the stored enum value (male=0..unisex=3).
func GenderValue(value string) *string {
	var out string
	switch value {
	case "male":
		out = "0"
	case "female":
		out = "1"
	case "kids":
		out = "2"
	case "unisex":
		out = "3"
	default:
		return nil
	}
	return &out
}

// ParseTime accepts a few common datetime formats.
func ParseTime(value string) *time.Time {
	for _, layout := range []string{time.RFC3339, "2006-01-02 15:04:05", "2006-01-02"} {
		if t, err := time.Parse(layout, value); err == nil {
			return &t
		}
	}
	return nil
}
