// Package scheduler runs registered jobs on an interval or once a day.
//
// This replaces Laravel's task Schedule. Jobs run sequentially inside the loop,
// so a slow job never overlaps with itself (Laravel's withoutOverlapping).
package scheduler

import (
	"context"
	"log"
	"time"
)

// Job is one scheduled task.
type Job struct {
	Name     string
	Every    time.Duration // set for interval jobs
	AtHour   int           // set for daily jobs (Every == 0)
	AtMinute int
	Run      func(ctx context.Context) error

	lastRun time.Time
}

// Scheduler holds the registered jobs.
type Scheduler struct {
	jobs []*Job
	// Tick is how often the scheduler checks for due jobs (default: 30s).
	Tick time.Duration
}

func New() *Scheduler { return &Scheduler{Tick: 30 * time.Second} }

// Every registers a job that runs on an interval.
func (s *Scheduler) Every(name string, every time.Duration, run func(ctx context.Context) error) *Scheduler {
	s.jobs = append(s.jobs, &Job{Name: name, Every: every, Run: run, lastRun: time.Now()})
	return s
}

// Daily registers a job that runs once a day at hour:minute.
func (s *Scheduler) Daily(name string, hour, minute int, run func(ctx context.Context) error) *Scheduler {
	s.jobs = append(s.jobs, &Job{Name: name, AtHour: hour, AtMinute: minute, Run: run})
	return s
}

// Run blocks until ctx is cancelled, checking for due jobs on each tick.
func (s *Scheduler) Run(ctx context.Context) {
	ticker := time.NewTicker(s.Tick)
	defer ticker.Stop()

	for {
		select {
		case <-ctx.Done():
			return
		case <-ticker.C:
			s.runDue(ctx)
		}
	}
}

func (s *Scheduler) runDue(ctx context.Context) {
	now := time.Now()
	for _, job := range s.jobs {
		if !job.due(now) {
			continue
		}
		job.lastRun = now

		start := time.Now()
		if err := job.Run(ctx); err != nil {
			log.Printf("scheduler: job %q failed: %v", job.Name, err)
			continue
		}
		log.Printf("scheduler: job %q done in %s", job.Name, time.Since(start).Round(time.Millisecond))
	}
}

func (j *Job) due(now time.Time) bool {
	if j.Every > 0 {
		return now.Sub(j.lastRun) >= j.Every
	}
	sameDay := j.lastRun.Year() == now.Year() && j.lastRun.YearDay() == now.YearDay()
	return now.Hour() == j.AtHour && now.Minute() == j.AtMinute && !sameDay
}
