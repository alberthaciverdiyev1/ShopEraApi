package config

import "fmt"

// DSN builds the PostgreSQL connection string.
func (c *Config) DSN() string {
	return fmt.Sprintf(
		"host=%s port=%s user=%s password=%s dbname=%s sslmode=%s",
		c.DB.Host, c.DB.Port, c.DB.User, c.DB.Password, c.DB.Name, c.DB.SSLMode,
	)
}

// Addr is the HTTP listen address.
func (c *Config) Addr() string {
	return fmt.Sprintf(":%d", c.App.Port)
}
