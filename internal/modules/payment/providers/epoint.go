package providers

import (
	"bytes"
	"context"
	"crypto/sha1"
	"encoding/base64"
	"encoding/json"
	"errors"
	"io"
	"net/http"
	"strconv"
	"time"
)

// Epoint is the Epoint (epoint.az) payment gateway.
type Epoint struct{}

func (Epoint) Key() string  { return "epoint" }
func (Epoint) Name() string { return "Epoint" }

type epointResponse struct {
	Status      string `json:"status"`
	RedirectURL string `json:"redirect_url"`
	Message     string `json:"message"`
}

// Initiate builds the signed request and returns Epoint's redirect URL.
func (Epoint) Initiate(ctx context.Context, req InitiateRequest, config map[string]string) (string, error) {
	publicKey := config["public_key"]
	privateKey := config["private_key"]
	if publicKey == "" || privateKey == "" {
		return "", errors.New("epoint: public_key/private_key not configured")
	}

	data := map[string]any{
		"public_key":           publicKey,
		"language":             orDefault(req.Language, "az"),
		"amount":               strconv.FormatFloat(req.Amount, 'f', 2, 64),
		"currency":             orDefault(req.Currency, "AZN"),
		"order_id":             req.OrderID,
		"description":          req.Description,
		"success_redirect_url": req.SuccessURL,
		"error_redirect_url":   req.ErrorURL,
	}
	dataJSON, err := json.Marshal(data)
	if err != nil {
		return "", err
	}

	body, err := json.Marshal(map[string]string{
		"data":      string(dataJSON),
		"signature": epointSign(privateKey, string(dataJSON)),
	})
	if err != nil {
		return "", err
	}

	httpReq, err := http.NewRequestWithContext(ctx, http.MethodPost, "https://epoint.az/api/1/request", bytes.NewReader(body))
	if err != nil {
		return "", err
	}
	httpReq.Header.Set("Content-Type", "application/json")

	client := &http.Client{Timeout: 20 * time.Second}
	resp, err := client.Do(httpReq)
	if err != nil {
		return "", err
	}
	defer resp.Body.Close()

	raw, _ := io.ReadAll(resp.Body)
	var parsed epointResponse
	if err := json.Unmarshal(raw, &parsed); err != nil {
		return "", err
	}
	if parsed.RedirectURL == "" {
		return "", errors.New("epoint: " + orDefault(parsed.Message, "payment could not be started"))
	}
	return parsed.RedirectURL, nil
}

// VerifyCallback validates the callback signature and parses the result.
func (Epoint) VerifyCallback(params map[string]string, config map[string]string) (*CallbackResult, error) {
	privateKey := config["private_key"]
	data := params["data"]
	if data == "" {
		return nil, errors.New("epoint: missing data")
	}
	if privateKey != "" && params["signature"] != epointSign(privateKey, data) {
		return nil, errors.New("epoint: invalid signature")
	}

	var payload map[string]any
	if err := json.Unmarshal([]byte(data), &payload); err != nil {
		return nil, err
	}

	orderID := stringValue(payload["order_id"])
	transactionID := stringValue(payload["transaction"])
	status := stringValue(payload["status"])

	return &CallbackResult{
		Provider:      "epoint",
		OrderID:       orderID,
		TransactionID: transactionID,
		Paid:          status == "success" || status == "paid",
		Raw:           params,
	}, nil
}

// epointSign is base64(sha1(privateKey + data + privateKey, raw)).
func epointSign(privateKey, data string) string {
	sum := sha1.Sum([]byte(privateKey + data + privateKey))
	return base64.StdEncoding.EncodeToString(sum[:])
}

func orDefault(value, fallback string) string {
	if value == "" {
		return fallback
	}
	return value
}

func stringValue(value any) string {
	if s, ok := value.(string); ok {
		return s
	}
	return ""
}
