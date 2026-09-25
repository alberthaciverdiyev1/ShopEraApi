package helpers_test

import (
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/gin-gonic/gin"

	"shopera/internal/helpers"
)

func perform(t *testing.T, target string) map[string]any {
	t.Helper()
	gin.SetMode(gin.TestMode)
	r := gin.New()
	r.GET("/", func(c *gin.Context) { helpers.Respond(c, http.StatusOK, "ok", gin.H{"id": 1}) })

	rec := httptest.NewRecorder()
	r.ServeHTTP(rec, httptest.NewRequest(http.MethodGet, target, nil))

	var body map[string]any
	if err := json.Unmarshal(rec.Body.Bytes(), &body); err != nil {
		t.Fatal(err)
	}
	return body
}

func TestEnvelope(t *testing.T) {
	body := perform(t, "/")
	if body["success"] != true || body["status_code"].(float64) != 200 || body["message"] != "ok" {
		t.Fatalf("unexpected envelope: %v", body)
	}
}

func TestMobileRaw(t *testing.T) {
	body := perform(t, "/?is_application=1")
	if body["id"].(float64) != 1 {
		t.Fatalf("expected raw data, got %v", body)
	}
}
