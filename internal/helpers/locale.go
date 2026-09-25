package helpers

import "strings"

// SupportedLocales are the locales used for translatable jsonb fields.
var SupportedLocales = []string{"az", "en", "ru", "tr"}

// ResolveLocale picks a locale from the Accept-Language header (default az).
func ResolveLocale(acceptLanguage string) string {
	if acceptLanguage == "" {
		return "az"
	}
	first := strings.ToLower(strings.TrimSpace(strings.Split(acceptLanguage, ",")[0]))
	if len(first) >= 2 {
		first = first[:2]
	}
	for _, locale := range SupportedLocales {
		if locale == first {
			return locale
		}
	}
	return "az"
}

// Trans picks a locale value from a translation map (fallback: en).
func Trans(value map[string]string, lang string) string {
	if value == nil {
		return ""
	}
	if v, ok := value[lang]; ok && v != "" {
		return v
	}
	return value["en"]
}
