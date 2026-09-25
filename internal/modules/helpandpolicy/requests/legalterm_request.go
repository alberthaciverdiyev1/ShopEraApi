package requests

// LegalTermUpdateRequest carries the translatable html.
type LegalTermUpdateRequest struct {
	HTML map[string]string `json:"html"`
	Type *string           `json:"type"`
}
