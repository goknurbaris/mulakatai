# Mulakat AI

AI-powered technical interview simulator built with Laravel.

## Current MVP

- Text-based interview flow (10 questions)
- Role track: Frontend / React
- Level selection: Junior or Mid
- Per-answer scoring with structured feedback:
  - Accuracy (40%)
  - Depth (25%)
  - Communication clarity (20%)
  - Problem-solving approach (15%)
- Final session report:
  - Total score
  - Top strengths
  - Top improvement areas
  - Per-question breakdown
- Auto-generated 7-day learning plan

## Tech

- Laravel 13
- Blade UI
- SQLite (default local setup)

## Run locally

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve --no-reload --host=127.0.0.1 --port=9000
```

Then open: `http://127.0.0.1:9000`

## Test

```bash
php artisan test
```

## AI scoring setup (OpenAI-compatible)

By default, the app uses deterministic fallback scoring.  
To enable real AI evaluation, set these in `.env`:

```bash
INTERVIEW_AI_ENABLED=true
INTERVIEW_AI_BASE_URL=https://api.openai.com
INTERVIEW_AI_CHAT_ENDPOINT=/v1/chat/completions
INTERVIEW_AI_MODEL=gpt-4.1-mini
INTERVIEW_AI_API_KEY=your_api_key
```
