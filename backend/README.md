# Visual Shield Backend

The backend powers authentication, video uploads, background analysis, report generation, exports, and admin endpoints for Visual Shield.

## Stack

- PHP 8.4 with Composer autoloading
- FastRoute for HTTP routing
- MySQL 8
- Nginx (Alpine) + PHP-FPM
- FFmpeg and FFprobe for video processing
- GD library for frame-level image analysis
- Docker Compose for local infrastructure

## Main responsibilities

- Register, log in, and log out users with JWT bearer authentication
- Store uploaded videos outside the public web root
- Queue and process video analysis jobs in a background worker container
- Detect flash frequency, luminance changes, and motion intensity
- Serve report data, segment data, datapoints, JSON exports, and CSV exports
- Provide admin endpoints for user role management

## Project layout

```text
backend/
|-- app/
|   |-- cli/            # Background worker (worker.php)
|   |-- public/         # API entry point (index.php) and route definitions
|   |-- src/
|   |   |-- Config/
|   |   |-- Controllers/
|   |   |-- DTOs/
|   |   |-- Exceptions/
|   |   |-- Framework/
|   |   |-- Models/
|   |   |-- Repositories/
|   |   |-- Services/
|   |   `-- Utils/
|   `-- composer.json
|-- database/
|   `-- migrations/     # SQL schema files, auto-run on first MySQL boot
|-- storage/
|   `-- videos/         # Uploaded video files stored outside web root
|-- .env.example
|-- docker-compose.yml
|-- nginx.conf
`-- PHP.Dockerfile
```

## Local setup

### Step 1 - Start the backend

Open a terminal in the `backend` folder and run:

```bash
cd backend
docker compose up -d --build
```

This starts all backend services:

- `nginx` on `http://localhost:8081`
- `phpmyadmin` on `http://localhost:8080`
- `mysql` on `localhost:3306`
- `php` (PHP-FPM application server, internal)
- `worker` (background analysis worker, internal)

Composer dependencies are installed automatically inside the container. The MySQL schema is initialized automatically from `database/migrations/` on the first database boot.

### Step 2 - Import the database

Open phpMyAdmin at `http://localhost:8080` and log in with:

| Field | Value |
|-------|-------|
| Username | `root` |
| Password | `root` |

Select the `visual_shield` database, go to the **Import** tab, and import:

```text
database/visual_shield.sql
```

This file is in the root-level `database/` folder of the project (one level above `backend/`).

### Step 3 - Verify the API

```text
GET http://localhost:8081/api/health
GET http://localhost:8081/api/health/db
```

## Environment variables

Default local values are defined in `.env.example`. A `.env` file is optional and only needed if you want to override ports or credentials.

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
JWT_SECRET=visual-shield-local-dev-secret
JWT_ISSUER=visual-shield
```

## API overview

Base URL:

```text
http://localhost:8081/api
```

Main route groups:

- Session: `/session`
- Auth: `/auth/register`, `/auth/login`, `/auth/logout`
- Users: `/users`, `/users/me`
- Videos: `/videos`, `/videos/{id}`, `/videos/{id}/reanalyze`, `/videos/{id}/stream`
- Reports: `/videos/{id}/report`, `/videos/{id}/report/json`, `/videos/{id}/report/csv`
- Segments and datapoints: `/videos/{id}/segments`, `/videos/{id}/datapoints`
- Admin: `/admin/users`, `/admin/users/{id}/role`
- Health and config: `/health`, `/health/db`, `/config`

Routes are registered in `app/public/index.php`.

## Worker

The `worker` service polls for queued videos and processes them continuously. To watch its output:

```bash
docker compose logs -f worker
```

## Architecture

The backend follows a controller -> service -> repository flow.

- Controllers handle HTTP input and output
- Services contain validation and business logic
- Repositories perform database queries using PDO prepared statements
- DTOs and models move typed data between layers
- Framework classes provide shared infrastructure: routing, JWT auth, and dependency wiring

## Notes

- Uploaded videos are stored in `storage/videos/`, outside the public web root
- The API returns JSON responses, including structured error responses
- Authenticated requests use the `Authorization: Bearer <jwt>` header
- Video streaming is handled through the backend, not by exposing uploaded files directly
- MySQL init scripts only run when the `mysql_data` volume is created for the first time
- To reset the local database, run `docker compose down -v` then `docker compose up -d --build`, then reimport the SQL dump
