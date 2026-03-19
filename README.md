# Visual Shield

Automated visual accessibility risk analysis for video content. Upload videos and get detailed reports on rapid flashing, brightness changes, and high motion intensity that may pose risks for viewers with photosensitive conditions.

> **Disclaimer:** Visual Shield does not provide medical diagnoses. It performs automated visual intensity screening based on predefined technical thresholds. Results are indicative and intended for accessibility awareness only.

---

## Features

- **Flash Detection** — Identifies rapid luminance changes between frames, calculates flash frequency per second, and flags segments exceeding safety thresholds
- **Motion Detection** — Measures pixel-level motion intensity between consecutive frames and flags sustained high-motion segments
- **Interactive Reports** — Timeline visualization, per-second charts (flash frequency, luminance, motion intensity), filterable segment tables
- **Export** — Download reports as JSON or CSV
- **Video Streaming** — In-browser video playback with synchronized analysis overlay
- **Admin Panel** — User management and role assignment
- **Dark/Light Theme** — Full theme support via CSS semantic tokens

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.4, bramus/router, PDO (MySQL) |
| Frontend | Vue 3 (Composition API), Vite 7, Tailwind CSS 4 |
| Database | MySQL 8.0 |
| Video Processing | FFmpeg / FFprobe |
| Image Analysis | PHP GD library |
| Charts | Chart.js + vue-chartjs |
| Infrastructure | Docker / Docker Compose |

---

## Architecture

Strict **MVC + Service + Repository + DTO** pattern with zero raw arrays crossing layer boundaries.

```
Controllers  →  Services  →  Repositories  →  MySQL
     ↕              ↕              ↕
    DTOs          Models         Models
```

- **Controllers** — HTTP layer only (parse input, call service, send response)
- **Services** — Business logic only (no HTTP globals, no presentation)
- **Repositories** — Database access only (return typed Models via `fromRow()`)
- **Models** — Readonly value objects (User, Video, Token, AnalysisResult, FlaggedSegment, AnalysisDatapoint)
- **DTOs** — Typed objects for cross-boundary data (input validation, service output, detector results)
- **ServiceRegistry** — Single wiring point for all dependencies

Frontend follows **Atomic Design**: atoms → molecules → organisms → templates → pages.

---

## Project Structure

```
visual-shield/
├── backend/
│   ├── app/
│   │   ├── public/index.php          # Entry point & router
│   │   ├── cli/worker.php            # Background analysis worker
│   │   ├── storage/videos/           # Uploaded video files
│   │   └── src/
│   │       ├── Controllers/          # AuthController, VideoController, ReportController, AdminController
│   │       ├── Services/             # AuthService, VideoService, AnalysisService, ReportService, etc.
│   │       ├── Repositories/         # UserRepository, VideoRepository, etc.
│   │       ├── Models/               # User, Video, Token, AnalysisResult, etc.
│   │       ├── DTOs/                 # RegisterDTO, ReportDTO, FlashAnalysisResult, etc.
│   │       ├── Config/               # AnalysisConfig (thresholds, limits, paths)
│   │       ├── Framework/            # BaseController, AuthMiddleware, ServiceRegistry, Database
│   │       ├── Utils/                # RiskLevel, ImageAnalyzer
│   │       └── Exceptions/           # ValidationException, NotFoundException, etc.
│   ├── database/migrations/          # SQL migration files (001–008)
│   ├── docker-compose.yml
│   ├── PHP.Dockerfile
│   └── nginx.conf
├── frontend/
│   └── src/
│       ├── api/                      # Axios API modules (auth, videos, users, admin, config)
│       ├── components/
│       │   ├── atoms/                # AppButton, Badge, StatCard, Spinner, Toast, etc.
│       │   ├── molecules/            # LoginForm, UploadForm, ExportButtons, etc.
│       │   ├── organisms/            # Header, SegmentTimeline, VideoOverlay, Charts, etc.
│       │   ├── templates/            # MainLayout, AuthTemplate, PageTemplate
│       │   └── pages/                # LoginPage, DashboardPage, ReportPage, AdminPage, etc.
│       ├── composables/              # useAuth, useToast, useTheme, useConfig
│       ├── router/                   # Vue Router with auth guards
│       └── utils/                    # Axios instance, formatters, colors, chart options
```

---

## Getting Started

### Prerequisites

- Docker & Docker Compose
- Node.js (^20.19.0 or >=22.12.0)

### 1. Clone

```bash
git clone https://github.com/SAINT-RONIN/visual-shield.git
cd visual-shield
```

### 2. Backend (Docker)

```bash
cd backend
cp .env.example .env        # Edit if needed — defaults work for local dev
docker-compose up -d --build
```

Apply database migrations (in order):

```bash
for f in database/migrations/*.sql; do
  docker-compose exec -T mysql mysql -u vs_user -pvs_password visual_shield < "$f"
done
```

Verify:

```bash
curl http://localhost:8081/api/health      # {"status":"ok"}
curl http://localhost:8081/api/health/db   # {"database":"connected"}
```

### 3. Frontend

```bash
cd frontend
npm install
npm run dev                  # http://localhost:5173
```

### 4. Start the Worker

The analysis worker runs as a separate Docker service and processes queued videos in the background:

```bash
cd backend
docker-compose up worker
```

---

## Docker Services

| Service | Purpose | Port |
|---------|---------|------|
| nginx | API gateway / reverse proxy | 8081 |
| php | PHP-FPM application server | internal |
| mysql | Database | 3306 |
| phpmyadmin | Database management UI | 8080 |
| worker | Background video analysis | — |

---

## API Endpoints

### Authentication
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/auth/register` | No | Register new user |
| POST | `/api/auth/login` | No | Login, returns Bearer token |
| POST | `/api/auth/logout` | Yes | Invalidate token |

### Users
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/users/me` | Yes | Get profile |
| PUT | `/api/users/me` | Yes | Update profile |

### Videos
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/videos` | Yes | Upload video |
| GET | `/api/videos` | Yes | List videos (paginated) |
| GET | `/api/videos/{id}` | Yes | Get video metadata |
| PATCH | `/api/videos/{id}` | Yes | Update video |
| DELETE | `/api/videos/{id}` | Yes | Delete video |
| PUT | `/api/videos/{id}/reanalyze` | Yes | Queue re-analysis |
| GET | `/api/videos/{id}/stream` | Yes | Stream video file |

### Reports
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/videos/{id}/report` | Yes | Get analysis report |
| GET | `/api/videos/{id}/report/json` | Yes | Export report as JSON |
| GET | `/api/videos/{id}/report/csv` | Yes | Export report as CSV |
| GET | `/api/videos/{id}/segments` | Yes | Get flagged segments |
| GET | `/api/videos/{id}/datapoints` | Yes | Get per-second metrics |

### Admin
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/admin/users` | Admin | List all users |
| PATCH | `/api/admin/users/{id}/role` | Admin | Update user role |

### Config & Health
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/config` | No | Public config (thresholds, limits) |
| GET | `/api/health` | No | API health check |
| GET | `/api/health/db` | No | Database health check |

---

## How It Works

1. **Upload** — User uploads a video file and selects a sampling rate (10/15/30/60 fps)
2. **Queue** — Video is stored with a UUID filename and status set to `queued`
3. **Process** — The worker extracts frames via FFmpeg, then runs flash and motion detectors on consecutive frame pairs
4. **Analyze** — Results are stored: per-second datapoints, flagged segments with severity levels, and aggregate statistics
5. **Report** — The frontend renders an interactive report with timeline visualization, charts, segment table, and export options

---

## Analysis Thresholds

| Parameter | Value | Description |
|-----------|-------|-------------|
| Flash threshold | 20 | Minimum luminance change to flag a flash |
| Flash danger frequency | 3/sec | Flashes per second triggering a warning |
| Motion threshold | 30 | Minimum pixel difference to count as motion |
| Motion severity (medium) | 60 | Motion intensity score for medium severity |
| Motion severity (high) | 120 | Motion intensity score for high severity |
| Max total frames | 10,000 | Cap on frames analyzed per video |
| Max file size | 500 MB | Upload size limit |

---

## Environment Variables

### Backend (`backend/.env`)
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

### Frontend (`frontend/.env`)
```env
VITE_API_BASE_URL=http://localhost:8081/api
```

---

## Security

- **Passwords** — Argon2id hashing
- **SQL Injection** — PDO prepared statements throughout
- **Command Injection** — `escapeshellarg()` on all dynamic shell values
- **File Uploads** — MIME validation, size limits, UUID filenames, storage outside web root
- **Authorization** — Every endpoint verifies resource ownership
- **CORS** — Restricted to configured frontend origin
- **Authentication** — Bearer tokens with 24-hour expiry

---

## License

This project is not currently licensed for public use.
