// Package helpers holds Product module specific helpers (localization).
package helpers

import "strings"

// SupportedLocales for translatable jsonb fields.
var SupportedLocales = []string{"az", "en", "ru", "tr"}

// ResolveLocale picks a locale from the Accept-Language header.
func ResolveLocale(acceptLanguage string) string {
	if acceptLanguage == "" {
		return "az"
	}
	first := strings.ToLower(strings.TrimSpace(strings.Split(acceptLanguage, ",")[0]))
	if len(first) >= 2 {
		first = first[:2]
	}
	for _, l := range SupportedLocales {
		if l == first {
			return l
		}
	}
	return "az"
}

// Trans picks a locale value from a jsonb translation map (fallback: en).
func Trans(value map[string]string, lang string) string {
	if value == nil {
		return ""
	}
	if v, ok := value[lang]; ok {
		return v
	}
	return value["en"]
}
