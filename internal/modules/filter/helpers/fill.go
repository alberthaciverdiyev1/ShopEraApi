// Package helpers holds Filter module specific helpers.
package helpers

import "strings"

var languages = []string{"az", "ru", "en", "tr"}

// FillLower completes missing languages from "az" (lower-cased, like Laravel).
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
