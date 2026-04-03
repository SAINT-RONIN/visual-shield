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

### 1. Optional: customize the environment

```powershell
Copy-Item .env.example .env
```

You only need a `.env` file if you want to override the default local ports or database credentials. The `docker-compose.yml` file now includes sensible local defaults.

### 2. Start the backend

```powershell
docker compose up -d --build
```

For a first-time setup, this one command now:

- `nginx` on `http://localhost:8081`
- `phpmyadmin` on `http://localhost:8080`
- `mysql` on `localhost:3306`
- `worker` for background analysis
- installs PHP dependencies automatically inside the mounted `app/` folder
- initializes the MySQL schema automatically from `database/migrations/` on the first database boot

### 3. Verify the API

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
docker compose logs -f worker
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
- MySQL init scripts only run when the `mysql_data` volume is created for the first time. If you need a fresh local database, run `docker compose down -v` and then `docker compose up -d --build`
