// Package epoint contains Epoint (epoint.az) specific integration code:
// its own signature, request format and callback parsing.
package epoint

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

	"shopera/internal/modules/payment/contract"
)

const (
	requestURL = "https://epoint.az/api/1/request"
	statusURL  = "https://epoint.az/api/1/get-status"
)

// Epoint is the Epoint payment gateway.
type Epoint struct{}

func New() Epoint { return Epoint{} }

func (Epoint) Key() string  { return "epoint" }
func (Epoint) Name() string { return "Epoint" }

type response struct {
	Status      string `json:"status"`
	RedirectURL string `json:"redirect_url"`
	Message     string `json:"message"`
}

// Initiate builds Epoint's signed request and returns its redirect URL.
func (Epoint) Initiate(ctx context.Context, req contract.InitiateRequest, config map[string]string) (string, error) {
	publicKey := config["public_key"]
	privateKey := config["private_key"]
	if publicKey == "" || privateKey == "" {
		return "", errors.New("epoint: public_key/private_key not configured")
	}

	data, _ := json.Marshal(map[string]any{
		"public_key":           publicKey,
		"language":             orDefault(req.Language, "az"),
		"amount":               strconv.FormatFloat(req.Amount, 'f', 2, 64),
		"currency":             orDefault(req.Currency, "AZN"),
		"order_id":             req.OrderID,
		"description":          req.Description,
		"success_redirect_url": req.SuccessURL,
		"error_redirect_url":   req.ErrorURL,
	})

	body, _ := json.Marshal(map[string]string{
		"data":      string(data),
		"signature": sign(privateKey, string(data)),
	})

	parsed, err := postJSON(ctx, requestURL, body)
	if err != nil {
		return "", err
	}
	if parsed.RedirectURL == "" {
		return "", errors.New("epoint: " + orDefault(parsed.Message, "payment could not be started"))
	}
	return parsed.RedirectURL, nil
}

// VerifyCallback validates Epoint's callback signature and parses the result.
func (Epoint) VerifyCallback(params map[string]string, config map[string]string) (*contract.CallbackResult, error) {
	data := params["data"]
	if data == "" {
		return nil, errors.New("epoint: missing data")
	}
	if privateKey := config["private_key"]; privateKey != "" && params["signature"] != sign(privateKey, data) {
		return nil, errors.New("epoint: invalid signature")
	}

	var payload map[string]any
	if err := json.Unmarshal([]byte(data), &payload); err != nil {
		return nil, err
	}
	status := strValue(payload["status"])

	return &contract.CallbackResult{
		Provider:      "epoint",
		OrderID:       strValue(payload["order_id"]),
		TransactionID: strValue(payload["transaction"]),
		Paid:          status == "success" || status == "paid",
		Raw:           params,
	}, nil
}

// Status fetches a transaction's status from Epoint (used by /payment/status).
func (Epoint) Status(ctx context.Context, transactionID string, config map[string]string) (string, error) {
	privateKey := config["private_key"]
	data, _ := json.Marshal(map[string]any{
		"public_key":  config["public_key"],
		"transaction": transactionID,
	})
	body, _ := json.Marshal(map[string]string{"data": string(data), "signature": sign(privateKey, string(data))})

	parsed, err := postJSON(ctx, statusURL, body)
	if err != nil {
		return "", err
	}
	return parsed.Status, nil
}

func postJSON(ctx context.Context, url string, body []byte) (*response, error) {
	httpReq, err := http.NewRequestWithContext(ctx, http.MethodPost, url, bytes.NewReader(body))
	if err != nil {
		return nil, err
	}
	httpReq.Header.Set("Content-Type", "application/json")

	client := &http.Client{Timeout: 20 * time.Second}
	resp, err := client.Do(httpReq)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	raw, _ := io.ReadAll(resp.Body)
	var parsed response
	if err := json.Unmarshal(raw, &parsed); err != nil {
		return nil, err
	}
	return &parsed, nil
}

// sign is base64(sha1(privateKey + data + privateKey, raw)).
func sign(privateKey, data string) string {
	sum := sha1.Sum([]byte(privateKey + data + privateKey))
	return base64.StdEncoding.EncodeToString(sum[:])
}

func orDefault(value, fallback string) string {
	if value == "" {
		return fallback
	}
	return value
}

func strValue(value any) string {
	if s, ok := value.(string); ok {
		return s
	}
	return ""
}
