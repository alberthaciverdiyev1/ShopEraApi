// Package database opens the GORM/PostgreSQL connection.
package database

import (
	"log"

	"gorm.io/driver/postgres"
	"gorm.io/gorm"

	"shopera/internal/config"
)

// Connect opens the DB. It does not ping, so the app can boot without a DB.
func Connect(cfg *config.Config) *gorm.DB {
	db, err := gorm.Open(postgres.Open(cfg.DSN()), &gorm.Config{
		TranslateError:                           true,
		DisableAutomaticPing:                     true,
		DisableForeignKeyConstraintWhenMigrating: true,
	})
	if err != nil {
		log.Fatalf("database: %v", err)
	}
	return db
}
