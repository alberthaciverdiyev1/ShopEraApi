package helpers

import "strings"

var supportedLanguages = []string{"az", "ru", "en", "tr"}

// FillLower completes missing languages from "az" and lower-cases them
// (Laravel Str::lower + Translate fallback).
// TODO: replace the az fallback with a real Google Translate call.
func FillLower(value map[string]string) map[string]string {
	if value == nil {
		value = map[string]string{}
	}
	source := value["az"]
	for _, lang := range supportedLanguages {
		if value[lang] == "" {
			value[lang] = source
		}
		value[lang] = strings.ToLower(value[lang])
	}
	return value
}
