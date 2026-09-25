package tests

import (
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"

	"github.com/gin-gonic/gin"

	"shopera/internal/config"
	"shopera/internal/httpserver"
)

func newEngine() *gin.Engine {
	gin.SetMode(gin.TestMode)
	return httpserver.New(config.Load(), nil)
}

func TestHealth(t *testing.T) {
	rec := httptest.NewRecorder()
	newEngine().ServeHTTP(rec, httptest.NewRequest(http.MethodGet, "/health", nil))
	if rec.Code != http.StatusOK {
		t.Fatalf("health status = %d", rec.Code)
	}
}

func TestRegisterValidation(t *testing.T) {
	rec := httptest.NewRecorder()
	req := httptest.NewRequest(http.MethodPost, "/api/auth/register", strings.NewReader(`{}`))
	req.Header.Set("Content-Type", "application/json")
	newEngine().ServeHTTP(rec, req)
	if rec.Code != http.StatusUnprocessableEntity {
		t.Fatalf("register status = %d, body=%s", rec.Code, rec.Body.String())
	}
}

func TestProtectedRouteRequiresToken(t *testing.T) {
	rec := httptest.NewRecorder()
	newEngine().ServeHTTP(rec, httptest.NewRequest(http.MethodPost, "/api/auth/logout", nil))
	if rec.Code != http.StatusUnauthorized {
		t.Fatalf("logout status = %d", rec.Code)
	}
}
