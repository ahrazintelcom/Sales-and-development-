# AI Client Hunter & Web App Studio

Internal portal for sales + delivery teams to find leads, generate AI-powered scripts, recommend web apps, and hand off to dev.

## Features
- Bootstrap 5 UI with Leads, Dev Pipeline, and Settings tabs
- CSV import + filters for lead discovery
- AI-generated call/email scripts, proposals, and app recommendations via OpenAI Chat Completions
- Pricing engine with configurable base prices and size multipliers
- Locked-in leads automatically create projects with AI spec drafts
- Dev pipeline board with tasks and optional GitHub integration hooks

## Getting Started
1. **Clone & install dependencies**
   ```bash
   git clone <repo>
   cd Sales-and-development-
   ```
2. **Configure environment**
   - Copy `.env.example` to `.env` and fill DB + OpenAI values.
   - Update `config/db.php` if not using env helper.
3. **Database**
   - Create MySQL DB `ai_client_hunter` (or change name in config).
   - Run SQL migrations from `/migrations` in order.
   - Seed at least one user:
     ```sql
     INSERT INTO users (name, email, password_hash, role) VALUES ('Admin', 'admin@example.com', PASSWORD('changeme'), 'admin');
     ```
     Replace `PASSWORD` with `password_hash('changeme', PASSWORD_DEFAULT)` via PHP CLI or admin tool.
4. **Run locally**
   ```bash
   php -S localhost:8000 -t public
   ```
   Visit http://localhost:8000 and log in.

## Configuring OpenAI & GitHub
- OpenAI: store API key + model via Settings tab (persisted in DB) or `.env` for default fallback.
- GitHub: add PAT, org, and template repo keys via Settings. `GitHubService` contains TODOs for actual REST calls.

## AI + External Services
- `src/Services/AIService.php` centralizes OpenAI usage. Replace placeholder responses once API key configured.
- `LeadFinderService` + `GitHubService` include TODO comments for plugging in real APIs.

## Tech Stack
- PHP 8+ with custom lightweight MVC
- MySQL (InnoDB, utf8mb4)
- Bootstrap 5 + Bootstrap Icons

## Scripts & Assets
- Custom JS in `assets/js/app.js` handles script regeneration & proposal modal.
- Styles in `assets/css/app.css`.

## Testing
Use `php -S` for manual testing. Add PHPUnit or feature tests as desired.
