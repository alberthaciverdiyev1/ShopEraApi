// Command migrate applies pending versioned migrations to the configured database.
package main

import (
	"log"

	"shopera/internal/config"
	"shopera/internal/platform/migrate"
)

func main() {
	cfg := config.Load()

	applied, err := migrate.RunSQL(cfg.DSN())
	if err != nil {
		log.Fatalf("migrate: %v", err)
	}
	if len(applied) == 0 {
		log.Println("no pending migrations")
		return
	}
	for _, version := range applied {
		log.Printf("applied: %s", version)
	}
}
