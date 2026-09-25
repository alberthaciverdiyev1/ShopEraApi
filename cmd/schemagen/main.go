// Command schemagen builds the full schema from the entities (one-time bootstrap).
package main

import (
	"log"

	"shopera/internal/config"
	"shopera/internal/database"
	"shopera/internal/platform/migrate"
)

func main() {
	cfg := config.Load()
	db := database.Connect(cfg)
	if err := migrate.AutoMigrate(db); err != nil {
		log.Fatalf("schemagen: %v", err)
	}
	log.Println("schema generated")
}
