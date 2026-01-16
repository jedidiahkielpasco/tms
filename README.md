# jedi-tms

Translation Management System (TMS) - A Laravel-based API for managing translations with tagging and CDN-ready JSON export.

## Getting Started

### Prerequisites

- Docker and Docker Compose installed on your system

### Running the Application

1. **Start all services:**
   ```bash
   docker compose up -d --build
   ```

   This command will:
   - Build the Docker images if needed
   - Start all containers in detached mode:
     - **tms**: Laravel application container
     - **db**: MySQL 8.0 database
     - **nginx**: Web server (accessible on port 8000)
     - **phpmyadmin**: Database management UI (accessible on port 8081)
     - **composer**: Helper service for running Composer commands
     - **artisan**: Helper service for running Laravel Artisan commands

2. **Run database migrations:**
   ```bash
   docker compose run --rm artisan migrate
   ```

3. **Access the application:**
   - **API**: http://localhost:8000/api
   - **Swagger/OpenAPI Documentation**: http://localhost:8000/docs
   - **phpMyAdmin**: http://localhost:8081

### Stopping the Application

To stop all services:
```bash
docker compose down
```

To stop and remove volumes (⚠️ this will delete database data):
```bash
docker compose down -v
```

## API Documentation

The API documentation is available via Swagger UI at `/docs`. This interactive documentation allows you to:

- View all available API endpoints
- Test API calls directly from the browser
- See request/response schemas
- Understand authentication requirements

The OpenAPI specification is defined in `public/openapi.json` and includes:

- **Translation Management**: Create, read, update translations
- **Tagging System**: Organize translations with tags
- **Export Endpoint**: Export translations as JSON for CDN integration with HTTP caching support

### API Endpoints

- `GET /api/translations` - List all translations (supports filtering by locale, key, tag, content)
- `POST /api/translations` - Create a new translation
- `GET /api/translations/{id}` - Get a specific translation
- `PUT /api/translations/{id}` - Update a translation
- `GET /api/export` - Export translations as JSON (supports locale and tag filtering)

All API endpoints require Bearer token authentication (Sanctum).

## Database Access

- **Host**: `db`
- **Database**: `tms`
- **Username**: `tms`
- **Password**: `p0t@t0_2026!`
- **Port**: `3306`

You can access the database via phpMyAdmin at http://localhost:8081 or by connecting directly to the `db` service from other containers.

## Helper Commands

### Running Composer Commands

```bash
docker compose run --rm composer install
docker compose run --rm composer update
```

### Running Artisan Commands

```bash
docker compose run --rm artisan migrate
docker compose run --rm artisan migrate:fresh --seed
docker compose run --rm artisan tinker
```

### Creating API Tokens

To create an API token for authentication:

```bash
# Create token for default user (test@example.com)
docker compose run --rm artisan user:create-token

# Create token for a specific user
docker compose run --rm artisan user:create-token user@example.com

# Create token with custom user name
docker compose run --rm artisan user:create-token user@example.com --name="John Doe"
```

The command will output the token that you can use in the `Authorization: Bearer <token>` header for API requests.

### Populating Translations

To populate the database with sample translations for testing:

```bash
# Populate with default 100,000 translations
docker compose run --rm artisan translations:populate

# Populate with a specific number of translations
docker compose run --rm artisan translations:populate 50000
```

This command creates translations in multiple locales (en, fr, es) with various tags (mobile, desktop, web, ios, android, backend, frontend) for performance testing.

## Project Structure

- `app/` - Laravel application code
- `config/` - Configuration files
- `database/migrations/` - Database migrations
- `routes/api.php` - API routes
- `public/openapi.json` - OpenAPI specification
- `docker-compose.yml` - Docker Compose configuration
- `Dockerfile` - Application container definition
