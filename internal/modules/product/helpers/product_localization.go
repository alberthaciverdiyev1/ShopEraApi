// Package helpers holds Product module specific helpers (localization).
package helpers

import "shopera/internal/helpers"

// SupportedLocales for translatable jsonb fields.
var SupportedLocales = helpers.SupportedLocales

// ResolveLocale picks a locale from the Accept-Language header.
func ResolveLocale(acceptLanguage string) string {
	return helpers.ResolveLocale(acceptLanguage)
}

// Trans picks a locale value from a jsonb translation map (fallback: en).
func Trans(value map[string]string, lang string) string {
	return helpers.Trans(value, lang)
}
