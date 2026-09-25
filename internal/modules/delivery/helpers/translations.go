// Package helpers holds Delivery module specific helpers.
package helpers

import "strings"

var languages = []string{"az", "ru", "en", "tr"}

// FillLower completes missing languages from "az" and lower-cases them.
// TODO: replace the az fallback with a real Google Translate call.
func FillLower(value map[string]string) map[string]string {
	if value == nil {
		value = map[string]string{}
	}
	source := value["az"]
	for _, lang := range languages {
		if value[lang] == "" {
			value[lang] = source
		}
		value[lang] = strings.ToLower(value[lang])
	}
	return value
}

// Key generates a city key from a name (Str::ascii + Str::studly approximation).
func Key(name string) string {
	replacer := strings.NewReplacer(
		"ə", "e", "ı", "i", "ş", "s", "ç", "c", "ğ", "g", "ö", "o", "ü", "u",
		"Ə", "E", "İ", "I", "Ş", "S", "Ç", "C", "Ğ", "G", "Ö", "O", "Ü", "U",
	)
	ascii := replacer.Replace(name)

	words := strings.FieldsFunc(ascii, func(r rune) bool {
		return !(r >= 'a' && r <= 'z' || r >= 'A' && r <= 'Z' || r >= '0' && r <= '9')
	})
	for i, w := range words {
		words[i] = strings.ToUpper(w[:1]) + w[1:]
	}
	return strings.Join(words, "")
}
