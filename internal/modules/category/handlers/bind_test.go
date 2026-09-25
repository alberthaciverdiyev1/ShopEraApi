package handlers

import (
	"bytes"
	"mime/multipart"
	"net/http/httptest"
	"testing"

	"github.com/gin-gonic/gin"
)

func TestBindCategorySaveMultipart(t *testing.T) {
	gin.SetMode(gin.TestMode)

	body := &bytes.Buffer{}
	w := multipart.NewWriter(body)
	_ = w.WriteField("name[az]", "Test")
	_ = w.WriteField("name[en]", "Test EN")
	_ = w.WriteField("description", "desc")
	_ = w.WriteField("sort_order", "3")
	_ = w.Close()

	c, _ := gin.CreateTestContext(httptest.NewRecorder())
	req := httptest.NewRequest("POST", "/", body)
	req.Header.Set("Content-Type", w.FormDataContentType())
	c.Request = req

	r := bindCategorySave(c)
	if r.Name["az"] != "Test" || r.Name["en"] != "Test EN" {
		t.Fatalf("name not bound: %#v", r.Name)
	}
	if r.Description == nil || *r.Description != "desc" {
		t.Fatalf("description not bound: %#v", r.Description)
	}
	if r.SortOrder == nil || *r.SortOrder != 3 {
		t.Fatalf("sort_order not bound: %#v", r.SortOrder)
	}
}
