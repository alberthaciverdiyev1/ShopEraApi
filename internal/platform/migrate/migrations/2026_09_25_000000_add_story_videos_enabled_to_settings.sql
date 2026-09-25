-- Adds the story-videos feature toggle to settings.
-- Apply against the existing PostgreSQL database (schema is otherwise managed by Laravel).
ALTER TABLE settings
    ADD COLUMN IF NOT EXISTS story_videos_enabled boolean NOT NULL DEFAULT false;
