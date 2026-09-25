package responses

import usermodels "shopera/internal/modules/user/models"

// Resource renders the admin user representation (Laravel UserResource).
// roleNames is nil when roles were not loaded, so the field is omitted.
func Resource(u *usermodels.User, totalBalance, effectiveMinimal float64, roleNames []string) map[string]any {
	payload := map[string]any{
		"id":                               u.ID,
		"name":                             titleCase(u.Name),
		"surname":                          titleCase(u.Surname),
		"is_active":                        u.IsActive,
		"is_wholesaler":                    u.IsWholesaler,
		"effective_minimal_purchase_price": effectiveMinimal,
		"email":                            u.Email,
		"phone":                            u.Phone,
		"total_balance":                    totalBalance,
		"referral_code":                    nil,
		"created_at":                       u.CreatedAt,
	}
	if roleNames != nil {
		payload["roles"] = roleNames
	}
	return payload
}

// titleCase capitalises the first letter of each word (Str::title equivalent).
func titleCase(value *string) *string {
	if value == nil || *value == "" {
		return value
	}
	runes := []rune(*value)
	capitalize := true
	for i, r := range runes {
		if capitalize && r >= 'a' && r <= 'z' {
			runes[i] = r - ('a' - 'A')
		}
		capitalize = r == ' ' || r == '-'
	}
	out := string(runes)
	return &out
}
