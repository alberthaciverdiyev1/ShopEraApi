// Command shopera runs the HTTP API (Gin + GORM + PostgreSQL).
package main

import (
	"log"

	"shopera/internal/config"
	"shopera/internal/database"
	"shopera/internal/httpserver"
)

func main() {
	cfg := config.Load()
	db := database.Connect(cfg)

	r := httpserver.New(cfg, db)

	log.Printf("Server listening at http://localhost%s", cfg.Addr())
	if err := r.Run(cfg.Addr()); err != nil {
		log.Fatalf("server: %v", err)
	}
}
