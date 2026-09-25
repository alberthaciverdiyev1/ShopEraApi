package migrate

import (
	"errors"
	"regexp"
	"strings"

	"gorm.io/driver/postgres"
	"gorm.io/gorm"

	"shopera/internal/config"
)

var databaseNamePattern = regexp.MustCompile(`^[a-z][a-z0-9_]{2,62}$`)

// ProvisionDatabase creates a database (if missing) and applies all migrations.
// This is what gives every new tenant its own, fully migrated database.
func ProvisionDatabase(name string) ([]string, error) {
	if !databaseNamePattern.MatchString(name) {
		return nil, errors.New("invalid database name")
	}

	cfg := config.Load()

	admin, err := gorm.Open(postgres.Open(cfg.DSNFor("postgres")), &gorm.Config{DisableAutomaticPing: true})
	if err != nil {
		return nil, err
	}
	if err := admin.Exec(`CREATE DATABASE "` + name + `"`).Error; err != nil {
		if !strings.Contains(err.Error(), "already exists") {
			return nil, err
		}
	}
	if sqlDB, err := admin.DB(); err == nil {
		_ = sqlDB.Close()
	}

	return RunSQL(cfg.DSNFor(name))
}
