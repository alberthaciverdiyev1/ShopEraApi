// Package helpers holds HelpAndPolicy module specific helpers.
package helpers

import (
	"strings"
	"unicode"
)

var languages = []string{"az", "ru", "en", "tr"}

// FillTitle completes missing languages from "az" and title-cases them.
// TODO: replace the az fallback with a real Google Translate call.
func FillTitle(value map[string]string) map[string]string {
	return fill(value, titleCase)
}

// FillUcfirst completes missing languages from "az" and upper-cases the first letter.
func FillUcfirst(value map[string]string) map[string]string {
	return fill(value, ucfirst)
}

func fill(value map[string]string, transform func(string) string) map[string]string {
	if value == nil {
		value = map[string]string{}
	}
	source := value["az"]
	for _, lang := range languages {
		if value[lang] == "" {
			value[lang] = source
		}
		value[lang] = transform(value[lang])
	}
	return value
}

func titleCase(s string) string {
	words := strings.Fields(s)
	for i, w := range words {
		words[i] = ucfirst(strings.ToLower(w))
	}
	return strings.Join(words, " ")
}

func ucfirst(s string) string {
	r := []rune(s)
	if len(r) == 0 {
		return s
	}
	r[0] = unicode.ToUpper(r[0])
	return string(r)
}
