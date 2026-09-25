// Package config loads environment settings (godotenv + os.Getenv).
package config

import "time"

// Config is the root configuration tree.
type Config struct {
	App AppConfig
	DB  DBConfig
	JWT JWTConfig
}

type AppConfig struct {
	Port int
	Env  string
}

type DBConfig struct {
	Host     string
	Port     string
	User     string
	Password string
	Name     string
	SSLMode  string
}

type JWTConfig struct {
	Secret string
	TTL    time.Duration
}

// Load reads .env (if present) and returns the configuration.
func Load() *Config {
	loadDotEnv(".env")

	ttl, err := time.ParseDuration(getEnv("JWT_TTL", "24h"))
	if err != nil {
		ttl = 24 * time.Hour
	}

	return &Config{
		App: AppConfig{
			Port: getEnvInt("APP_PORT", 3000),
			Env:  getEnv("APP_ENV", "development"),
		},
		DB: DBConfig{
			Host:     getEnv("DB_HOST", "127.0.0.1"),
			Port:     getEnv("DB_PORT", "5432"),
			User:     getEnv("DB_USER", "postgres"),
			Password: getEnv("DB_PASSWORD", "a"),
			Name:     getEnv("DB_NAME", "shopera"),
			SSLMode:  getEnv("DB_SSLMODE", "disable"),
		},
		JWT: JWTConfig{
			Secret: getEnv("JWT_SECRET", "change-me-in-production"),
			TTL:    ttl,
		},
	}
}
