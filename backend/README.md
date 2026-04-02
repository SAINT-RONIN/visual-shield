# Visual Shield Backend

The backend powers authentication, video uploads, background analysis, report generation, exports, and admin endpoints for Visual Shield.

## Stack

- PHP 8.4 with Composer autoloading
- FastRoute for HTTP routing
- MySQL 8
- Nginx + PHP-FPM
- FFmpeg and FFprobe for video processing
- GD for frame analysis
- Docker Compose for local infrastructure

## Main Responsibilities

- Register, log in, and log out users with Bearer token authentication
- Store uploaded videos outside the public web root
- Queue and process video analysis jobs in a worker container
- Detect flash frequency, luminance changes, and motion intensity
- Serve report data, segment data, datapoints, JSON exports, and CSV exports
- Provide admin endpoints for user management

## Project Layout

```text
backend/
|-- app/
|   |-- cli/            # Background worker
|   |-- public/         # API entry point and routes
|   |-- src/
|   |   |-- Config/
|   |   |-- Controllers/
|   |   |-- Contracts/
|   |   |-- DTOs/
|   |   |-- Exceptions/
|   |   |-- Framework/
|   |   |-- Models/
|   |   |-- Repositories/
|   |   |-- Services/
|   |   `-- Utils/
|   `-- composer.json
|-- database/
|   `-- migrations/
|-- storage/
|   `-- videos/
|-- .env.example
|-- docker-compose.yml
|-- nginx.conf
`-- PHP.Dockerfile
```

## Local Setup

### 1. Create the environment file

```powershell
Copy-Item .env.example .env
```

### 2. Start the containers

```powershell
docker-compose up -d --build
```

This starts:

- `nginx` on `http://localhost:8081`
- `phpmyadmin` on `http://localhost:8080`
- `mysql` on `localhost:3306`
- `worker` for background analysis

### 3. Install PHP dependencies

```powershell
docker-compose exec php composer install
```

### 4. Run the database migrations

Recommended local option:

1. Open `http://localhost:8080`
2. Sign in with the MySQL root credentials from `.env`
3. Select the `visual_shield` database
4. Import the SQL files in `database/migrations/` in numeric order

Current migration files:

- `001_create_users_table.sql`
- `002_create_auth_tokens_table.sql`
- `003_create_videos_table.sql`
- `004_create_analysis_results_table.sql`
- `005_create_flagged_segments_table.sql`
- `006_create_analysis_datapoints_table.sql`
- `007_add_video_progress_and_error.sql`
- `008_add_user_role.sql`

### 5. Verify the API

```text
GET http://localhost:8081/api/health
GET http://localhost:8081/api/health/db
```

## Environment Variables

Default local values are defined in `.env.example`.

```env
MYSQL_ROOT_PASSWORD=root
MYSQL_DATABASE=visual_shield
MYSQL_USER=vs_user
MYSQL_PASSWORD=vs_password
MYSQL_HOST=mysql
MYSQL_PORT=3306
NGINX_PORT=8081
PHPMYADMIN_PORT=8080
CORS_ORIGIN=http://localhost:5173
```

## API Overview

Base URL:

```text
http://localhost:8081/api
```

Main route groups:

- Auth: `/auth/register`, `/auth/login`, `/auth/logout`
- User profile: `/users/me`
- Videos: `/videos`, `/videos/{id}`, `/videos/{id}/reanalyze`, `/videos/{id}/stream`
- Reports: `/videos/{id}/report`, `/videos/{id}/report/json`, `/videos/{id}/report/csv`
- Admin: `/admin/users`, `/admin/users/{id}/role`
- Health and config: `/health`, `/health/db`, `/config`

Routes are registered in `app/public/index.php`.

## Worker

The `worker` service polls for queued videos and processes them continuously. If you want to watch it separately:

```powershell
docker-compose logs -f worker
```

## Architecture

The backend follows a controller -> service -> repository flow.

- Controllers handle HTTP input and output
- Services contain validation and business logic
- Repositories perform database queries
- DTOs and models move typed data between layers
- Framework classes provide shared infrastructure such as routing, auth, and service wiring

## Notes

- Uploaded videos are stored in `storage/videos/`
- The API returns JSON responses, including structured error responses
- Authenticated requests use the `Authorization: Bearer <token>` header
- Streaming is handled through the backend, not by exposing uploaded files directly
