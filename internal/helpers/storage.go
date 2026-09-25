package helpers

import (
	"fmt"
	"os"
	"path/filepath"
	"strings"
	"time"

	"github.com/gin-gonic/gin"
)

// SaveUpload stores an uploaded file under storage/app/public/<dir> and returns
// its relative path (Laravel Storage::disk('public')->store karşılığı).
func SaveUpload(c *gin.Context, field, dir string) (string, error) {
	file, err := c.FormFile(field)
	if err != nil {
		return "", err
	}
	name := fmt.Sprintf("%d_%s", time.Now().Unix(), filepath.Base(file.Filename))
	target := filepath.Join("storage", "app", "public", dir)
	if err := os.MkdirAll(target, 0o755); err != nil {
		return "", err
	}
	path := filepath.Join(target, name)
	if err := c.SaveUploadedFile(file, path); err != nil {
		return "", err
	}
	return dir + "/" + name, nil
}

// StorageURL turns a stored relative path into a public URL (Laravel Storage::url).
func StorageURL(path string) string {
	if path == "" {
		return ""
	}
	if strings.HasPrefix(path, "http://") || strings.HasPrefix(path, "https://") {
		return path
	}
	base := strings.TrimRight(os.Getenv("APP_URL"), "/")
	if base == "" {
		base = "http://localhost:8000"
	}
	return base + "/storage/" + strings.TrimLeft(path, "/")
}
