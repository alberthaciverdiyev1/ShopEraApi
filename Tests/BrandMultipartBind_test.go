package tests

import (
	"bytes"
	"mime/multipart"
	"net/http/httptest"
	"testing"

	"github.com/gin-gonic/gin"

	brandrequests "shopera/internal/modules/brand/requests"
)

func TestBrandMultipartBind(t *testing.T) {
	gin.SetMode(gin.TestMode)
	body := &bytes.Buffer{}
	w := multipart.NewWriter(body)
	_ = w.WriteField("name", "Nike")
	_ = w.WriteField("sort_order", "2")
	_ = w.Close()

	c, _ := gin.CreateTestContext(httptest.NewRecorder())
	req := httptest.NewRequest("POST", "/", body)
	req.Header.Set("Content-Type", w.FormDataContentType())
	c.Request = req

	var r brandrequests.SaveRequest
	if err := c.ShouldBind(&r); err != nil {
		t.Fatalf("bind: %v", err)
	}
	if r.Name != "Nike" {
		t.Fatalf("name not bound: %q", r.Name)
	}
	if r.SortOrder == nil || *r.SortOrder != 2 {
		t.Fatalf("sort not bound: %#v", r.SortOrder)
	}
}
