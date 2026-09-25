// Command provision creates a tenant database and migrates it.
// Usage: go run ./cmd/provision <database_name>
package main

import (
	"log"
	"os"

	"shopera/internal/platform/migrate"
)

func main() {
	if len(os.Args) < 2 {
		log.Fatal("usage: provision <database_name>")
	}
	applied, err := migrate.ProvisionDatabase(os.Args[1])
	if err != nil {
		log.Fatalf("provision: %v", err)
	}
	log.Printf("database %q ready (%d migrations applied)", os.Args[1], len(applied))
}
