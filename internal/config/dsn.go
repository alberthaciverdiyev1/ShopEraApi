package config

import "fmt"

// DSN builds the PostgreSQL connection string.
func (c *Config) DSN() string { return c.DSNFor(c.DB.Name) }

// DSNFor builds a connection string for a specific database (tenant provisioning).
func (c *Config) DSNFor(dbName string) string {
	return fmt.Sprintf(
		"host=%s port=%s user=%s password=%s dbname=%s sslmode=%s",
		c.DB.Host, c.DB.Port, c.DB.User, c.DB.Password, dbName, c.DB.SSLMode,
	)
}

// Addr is the HTTP listen address.
func (c *Config) Addr() string {
	return fmt.Sprintf(":%d", c.App.Port)
}
