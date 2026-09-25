// Command scheduler runs background jobs: expiring unpaid orders on an interval.
//
// Run it alongside the API (e.g. as a systemd service or in a separate
// container). It reuses the same configuration as the HTTP server.
package main

import (
	"context"
	"flag"
	"log"
	"os/signal"
	"syscall"
	"time"

	"shopera/internal/config"
	"shopera/internal/database"
	orderservices "shopera/internal/modules/order/services"
	"shopera/internal/platform/scheduler"
)

func main() {
	once := flag.Bool("once", false, "run the expiry job once and exit")
	flag.Parse()

	cfg := config.Load()
	db := database.Connect(cfg)
	expiry := orderservices.NewPaymentExpiryService(db)

	if *once {
		summary, err := expiry.Expire(3, 200)
		if err != nil {
			log.Fatalf("scheduler: expire failed: %v", err)
		}
		log.Printf("scheduler: expire-pending-payments selected=%d expired=%d skipped=%d remaining=%d",
			summary.Selected, summary.Expired, summary.Skipped, summary.Remaining)
		return
	}

	ctx, stop := signal.NotifyContext(context.Background(), syscall.SIGINT, syscall.SIGTERM)
	defer stop()

	log.Println("scheduler: started")

	scheduler.New().
		Every("expire-pending-payments", 15*time.Minute, func(ctx context.Context) error {
			summary, err := expiry.Expire(3, 200)
			if err != nil {
				return err
			}
			log.Printf("scheduler: expire-pending-payments selected=%d expired=%d skipped=%d remaining=%d",
				summary.Selected, summary.Expired, summary.Skipped, summary.Remaining)
			return nil
		}).
		Run(ctx)

	log.Println("scheduler: stopped")
}
