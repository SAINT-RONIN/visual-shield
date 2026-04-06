# Visual Shield Backend

The backend powers authentication, video uploads, background analysis, report generation, exports, and admin endpoints for Visual Shield.

## Stack

- PHP 8.4, FastRoute, Composer
- MySQL 8, Nginx (Alpine), PHP-FPM
- FFmpeg and FFprobe for video processing
- GD library for frame-level image analysis
- Docker Compose

## Project layout

```text
backend/
|-- app/
|   |-- cli/            # Background worker
|   |-- public/         # API entry point and routes
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
|   `-- migrations/
|-- storage/
|   `-- videos/
|-- docker-compose.yml
|-- PHP.Dockerfile
`-- nginx.conf
```

## Setup

### Step 1 - Start the backend

```bash
cd backend
docker compose up -d --build
```

Starts nginx, PHP-FPM, worker, MySQL, and phpMyAdmin. Composer dependencies install automatically inside the container.

### Step 2 - Import the database (required)

> **IMPORTANT:** You must import the database before using the app.

Open phpMyAdmin at `http://localhost:8080` and log in with:

| Field | Value |
|-------|-------|
| Username | `root` |
| Password | `Secret123@` |

Select the `visual_shield` database, go to the **Import** tab, and import:

```text
database/visual_shield.sql
```

This file is in the root-level `database/` folder, one level above `backend/`.

### Step 3 - Verify

```text
GET http://localhost:8081/api/health
GET http://localhost:8081/api/health/db
```

## Environment variables

Defaults in `.env.example` work for local development. A `.env` file is only needed to override ports or credentials.

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

## API routes

Base URL: `http://localhost:8081/api`

- Auth: `/auth/register`, `/auth/login`, `/auth/logout`
- Users: `/users/me`
- Videos: `/videos`, `/videos/{id}`, `/videos/{id}/reanalyze`, `/videos/{id}/stream`
- Reports: `/videos/{id}/report`, `/videos/{id}/report/json`, `/videos/{id}/report/csv`
- Admin: `/admin/users`, `/admin/users/{id}/role`, `/admin/users/{id}/deactivate`, `/admin/users/{id}/activate`
- Health: `/health`, `/health/db`, `/config`

## Architecture

Controller -> Service -> Repository.

- Controllers handle HTTP input and output
- Services contain business logic and validation
- Repositories handle database queries with PDO prepared statements
- DTOs move typed data between layers

## Notes

- Uploaded videos are stored in `storage/videos/`, outside the public web root
- Authenticated requests use `Authorization: Bearer <jwt>`
- To reset the database: `docker compose down -v` then `docker compose up -d --build`, then reimport the SQL dump
