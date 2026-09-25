package migrate

import (
	"embed"
	"sort"
	"strings"

	"gorm.io/driver/postgres"
	"gorm.io/gorm"
)

//go:embed migrations/*.sql
var migrationsFS embed.FS

// Open connects for migrations using the simple protocol so a whole .sql file
// (multiple statements) can run in one Exec.
func Open(dsn string) (*gorm.DB, error) {
	if !strings.Contains(dsn, "default_query_exec_mode") {
		dsn += " default_query_exec_mode=simple_protocol"
	}
	return gorm.Open(postgres.Open(dsn), &gorm.Config{DisableAutomaticPing: true})
}

// RunSQL applies pending versioned SQL migrations (tracked in schema_migrations).
func RunSQL(dsn string) ([]string, error) {
	db, err := Open(dsn)
	if err != nil {
		return nil, err
	}
	return runMigrations(db)
}

func runMigrations(db *gorm.DB) ([]string, error) {
	if err := db.Exec(`CREATE TABLE IF NOT EXISTS public.schema_migrations (
		version text PRIMARY KEY,
		applied_at timestamptz NOT NULL DEFAULT now()
	)`).Error; err != nil {
		return nil, err
	}

	entries, err := migrationsFS.ReadDir("migrations")
	if err != nil {
		return nil, err
	}
	names := make([]string, 0, len(entries))
	for _, e := range entries {
		if !e.IsDir() {
			names = append(names, e.Name())
		}
	}
	sort.Strings(names)

	applied := make([]string, 0, len(names))
	for _, name := range names {
		var count int64
		if err := db.Raw("SELECT COUNT(*) FROM public.schema_migrations WHERE version = ?", name).Scan(&count).Error; err != nil {
			return applied, err
		}
		if count > 0 {
			continue
		}

		content, err := migrationsFS.ReadFile("migrations/" + name)
		if err != nil {
			return applied, err
		}

		err = db.Transaction(func(tx *gorm.DB) error {
			// pg_dump files set search_path to '' — restore it for the next statements.
			if err := tx.Exec("SET search_path TO public").Error; err != nil {
				return err
			}
			if err := tx.Exec(string(content)).Error; err != nil {
				return err
			}
			return tx.Exec("INSERT INTO public.schema_migrations (version) VALUES (?)", name).Error
		})
		if err != nil {
			return applied, err
		}
		applied = append(applied, name)
	}
	return applied, nil
}
