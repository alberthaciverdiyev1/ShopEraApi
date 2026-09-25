package config

import (
	"bufio"
	"log"
	"os"
	"strconv"
	"strings"

	"github.com/joho/godotenv"
)

// loadDotEnv loads a .env file if present (existing env vars win).
func loadDotEnv(path string) {
	if err := godotenv.Load(path); err != nil {
		log.Println("config: .env not found, using environment variables")
	}
	// Fallback: read KEY=VALUE manually if godotenv found nothing.
	_ = readManualEnv(path)
}

func readManualEnv(path string) error {
	file, err := os.Open(path)
	if err != nil {
		return err
	}
	defer file.Close()

	scanner := bufio.NewScanner(file)
	for scanner.Scan() {
		line := strings.TrimSpace(scanner.Text())
		if line == "" || strings.HasPrefix(line, "#") {
			continue
		}
		key, value, found := strings.Cut(line, "=")
		if !found {
			continue
		}
		if _, exists := os.LookupEnv(strings.TrimSpace(key)); !exists {
			_ = os.Setenv(strings.TrimSpace(key), strings.Trim(strings.TrimSpace(value), `"'`))
		}
	}
	return nil
}

func getEnv(key, fallback string) string {
	if v, ok := os.LookupEnv(key); ok {
		return v
	}
	return fallback
}

func getEnvInt(key string, fallback int) int {
	if v, err := strconv.Atoi(getEnv(key, "")); err == nil {
		return v
	}
	return fallback
}

// getEnvList reads a comma-separated env var into a trimmed slice.
func getEnvList(key string) []string {
	raw := strings.TrimSpace(getEnv(key, ""))
	if raw == "" {
		return nil
	}
	parts := strings.Split(raw, ",")
	out := make([]string, 0, len(parts))
	for _, p := range parts {
		if p = strings.TrimSpace(p); p != "" {
			out = append(out, p)
		}
	}
	return out
}
