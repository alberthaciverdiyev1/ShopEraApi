// Package helpers holds Category module specific helpers.
package helpers

import (
	"strings"
	"unicode"
)

var languages = []string{"az", "ru", "en", "tr"}

// TitleCase upper-cases the first letter of each word (Laravel Str::title).
func TitleCase(s string) string {
	words := strings.Fields(s)
	for i, w := range words {
		words[i] = upperFirst(strings.ToLower(w))
	}
	return strings.Join(words, " ")
}

// FillTranslations completes missing languages from "az" and title-cases them.
// TODO: replace the az fallback with a real Google Translate call.
func FillTranslations(name map[string]string) map[string]string {
	if name == nil {
		name = map[string]string{}
	}
	source := name["az"]
	for _, lang := range languages {
		if name[lang] == "" {
			name[lang] = source
		}
		name[lang] = TitleCase(name[lang])
	}
	return name
}

func upperFirst(s string) string {
	r := []rune(s)
	if len(r) == 0 {
		return s
	}
	r[0] = unicode.ToUpper(r[0])
	return string(r)
}
