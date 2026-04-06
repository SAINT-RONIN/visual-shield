# Visual Shield

An Automated Video Accessibility Risk Analysis Web Application

| Field | Value |
|-------|-------|
| Project | Visual Shield |
| Type | Full-stack web application |
| Backend | PHP 8.4, MySQL 8, Docker Compose |
| Frontend | Vue 3, Vite 7, Tailwind CSS 4 |
| Focus | Flash, luminance, and motion risk analysis for video |

---

## About this project

Visual Shield is a web application that lets users upload videos and receive automated accessibility-focused reports about visual triggers that may affect viewers with photosensitive conditions.

The system analyses rapid flashing, luminance changes, and strong motion intensity in uploaded video files. After processing, the app presents an interactive report with charts, flagged segments, exports, and streaming support so the user can review the video alongside the analysis results.

---

IMPORTANT NOTE: THIS APP IS SUBJECT TO EXPANSION AND CHANGES IN THE FUTURE.

Visual Shield does not provide medical advice or medical diagnosis. The analysis is automated and based on technical thresholds for accessibility awareness only.

Additional information can be found in [AI-DISCLOUSURE.md](./AI-DISCLOUSURE.md), [backend/README.md](./backend/README.md), and [frontend/README.md](./frontend/README.md).

[![Go to Setup](https://img.shields.io/badge/CLICK%20HERE%20TO%20GO%20TO-SETUP%20%26%20RUNNING%20THE%20PROJECT-blue?style=for-the-badge)](#setup-and-running-the-project)

---

### Who is it for?

Visual Shield is for people who want to screen video content for possible accessibility risks before sharing or reviewing it. That includes teachers, students, content creators, and anyone who wants a clearer technical overview of risky visual patterns in video.

### Tech stack

| Component | Technology |
|-----------|------------|
| Backend language | PHP 8.4 |
| Backend routing | FastRoute |
| Frontend framework | Vue 3 with Composition API |
| Frontend tooling | Vite 7 |
| Styling | Tailwind CSS 4 |
| Database | MySQL 8 |
| Charts | Chart.js + vue-chartjs |
| Video processing | FFmpeg + FFprobe |
| Image analysis | PHP GD |
| Web server | Nginx (Alpine) |
| Containers | Docker Compose |

---

## What can this app do?

Visual Shield includes the main features needed to upload videos, process them, and review the results in a structured way.

### User management

- Register a new account
- Log in and log out with JWT bearer authentication
- View and update your own profile
- Access protected routes only when authenticated

### Video management

- Upload a video file for analysis
- Choose a sampling rate for frame extraction
- View your uploaded video library
- Reanalyze a video when needed
- Delete videos
- Stream uploaded videos through the backend

### Accessibility analysis

- Detect rapid flash events based on luminance changes between frames
- Measure motion intensity between consecutive frames
- Store per-second datapoints for charting and reporting
- Flag risky segments with severity levels
- Produce an overall risk summary for the uploaded video

### Reports and exports

- View an interactive report page for each analyzed video
- See charts for flash frequency, luminance, and motion intensity
- Review flagged segments in timeline and table form
- Export report data as JSON
- Export flagged segment data as CSV

### Admin features

- View a list of all users
- Change a user's role between `viewer` and `admin`

### Security and technical safeguards

- Password hashing with Argon2id
- PDO prepared statements for database queries
- JWT bearer authentication
- Uploaded files stored outside the public web root
- File size limits and validation checks during upload
- Configurable CORS origin for frontend access

### Main API route groups

- `/api/session`
- `/api/auth/register`, `/api/auth/login`, `/api/auth/logout`
- `/api/users`, `/api/users/me`
- `/api/videos`
- `/api/videos/{id}`
- `/api/videos/{id}/reanalyze`
- `/api/videos/{id}/stream`
- `/api/videos/{id}/report`
- `/api/videos/{id}/report/json`
- `/api/videos/{id}/report/csv`
- `/api/videos/{id}/segments`
- `/api/videos/{id}/datapoints`
- `/api/admin/users`
- `/api/admin/users/{id}/role`
- `/api/config`
- `/api/health`
- `/api/health/db`

---

## Setup and running the project

### What you need

- Docker Desktop installed and running
- Node.js `^20.19.0 || >=22.12.0`
- npm

### Quick start

**Step 1 - Start the backend**

Open a terminal, navigate to the `backend` folder, and run:

```bash
cd backend
docker compose up -d --build
```

This single command starts all backend containers: Nginx, PHP-FPM, the background worker, MySQL, and phpMyAdmin. Composer dependencies are installed automatically inside the container and the MySQL schema is initialized automatically from the migration files on first boot.

**Step 2 - Import the database (required)**

> **IMPORTANT:** You must import the database before using the app. Skipping this step will cause the application to fail.

Once the containers are running, open phpMyAdmin at:

```
http://localhost:8080
```

Log in with:

| Field | Value |
|-------|-------|
| Username | `root` |
| Password | `Secret123@` |

Select the `visual_shield` database, go to the **Import** tab, and import the file located at:

```text
database/visual_shield.sql
```

This file is in the root `database/` folder of the project, not inside `backend/`.

**Step 3 - Start the frontend**

Open a second terminal, navigate to the `frontend` folder, and run:

```bash
cd frontend
npm install
npm run dev
```

**Step 4 - Open the app**

Go to `http://localhost:5173` in your browser.

---

### Service URLs

| Service | URL |
|---------|-----|
| Frontend | `http://localhost:5173` |
| Backend API | `http://localhost:8081/api` |
| Backend health check | `http://localhost:8081/api/health` |
| Backend DB health check | `http://localhost:8081/api/health/db` |
| phpMyAdmin | `http://localhost:8080` |

### Container overview

| Container | Port | Purpose |
|-----------|------|---------|
| `nginx` | `8081` | Serves the backend API |
| `php` | internal | Runs the PHP-FPM application |
| `worker` | internal | Processes queued video analysis jobs |
| `mysql` | `3306` | Database server |
| `phpmyadmin` | `8080` | Database management UI |

### Environment files

Backend defaults already work for local development. A `backend/.env` file is optional and only needed if you want to override ports or credentials.

Default backend values:

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

Frontend default value:

```env
VITE_API_BASE_URL=http://localhost:8081/api
```

### Common commands

```bash
# Backend: start
cd backend
docker compose up -d --build

# Backend: stop
docker compose down

# Backend: fresh start (also resets database volume)
docker compose down -v

# Backend: view worker logs
docker compose logs -f worker

# Backend: view all logs
docker compose logs -f

# Frontend: install and run
cd frontend
npm install
npm run dev

# Frontend: production build
npm run build

# Frontend: preview production build
npm run preview
```

### Troubleshooting

| Problem | Solution |
|---------|----------|
| Backend containers start but the database looks old | Run `docker compose down -v` inside `backend/`, then `docker compose up -d --build`, then reimport `database/visual_shield.sql` |
| Frontend cannot connect to the API | Check that the backend is running and that `VITE_API_BASE_URL` in `frontend/.env` points to `http://localhost:8081/api` |
| Port already in use | Change the port in `backend/.env` or stop the conflicting local service |
| Video analysis is not progressing | Check `docker compose logs -f worker` inside the `backend/` folder |
| `npm install` fails | Make sure you are using a supported Node.js version (`^20.19.0 \|\| >=22.12.0`) |

---

## Accounts and testing

After importing the database you will have pre-existing user accounts available for testing:

| Role | Username | Password |
|------|----------|----------|
| Viewer | `TestUser1` | `Password123!` |
| Admin | `Admin` | `Admin123!` |

To promote a user to admin, run this query in phpMyAdmin on the `visual_shield` database:

```sql
UPDATE users
SET role = 'admin'
WHERE username = 'your_username';
```

The `videos-to-use/` folder contains some sample videos for quick testing.

---

## How the analysis works

1. A user uploads a video and selects a sampling rate
2. The backend stores the file outside the public web root and creates a queued job
3. The worker container picks up the job and extracts frames using FFmpeg
4. The analysis logic compares consecutive frames to calculate flash and motion metrics
5. Results are saved in MySQL as analysis results, flagged segments, and per-second datapoints
6. The frontend displays the report with charts, segments, and export options

Current backend analysis parameters:

| Parameter | Value | Description |
|-----------|-------|-------------|
| Allowed sampling rates | `10, 15, 30, 60` fps | User-selectable analysis rates |

> **Note on sampling rates:** 60 fps could not be properly tested during development as it caused my system(Laptop) to glitch. I recommend to test with 15 to 30 fps. If you have a powerful laptop/pc then go ahead and test 60fps.
| Max file size | `500 MB` | Maximum upload size |
| Max total frames | `10,000` | Cap on processed frames per video |
| Flash threshold | `20` | Minimum luminance delta to count as a flash |
| Flash danger frequency | `3/sec` | Warning threshold for dangerous flash frequency |
| Motion threshold | `30` | Minimum pixel difference to count as motion |
| Motion severity medium | `60` | Medium motion intensity threshold |
| Motion severity high | `120` | High motion intensity threshold |

---

## Project structure

```text
visual-shield/
|-- backend/
|   |-- app/
|   |   |-- cli/                 # Background worker (worker.php)
|   |   |-- public/              # API entry point (index.php) and routes
|   |   |-- src/
|   |   |   |-- Config/
|   |   |   |-- Controllers/
|   |   |   |-- DTOs/
|   |   |   |-- Exceptions/
|   |   |   |-- Framework/
|   |   |   |-- Models/
|   |   |   |-- Repositories/
|   |   |   |-- Services/
|   |   |   `-- Utils/
|   |   `-- composer.json
|   |-- database/
|   |   `-- migrations/          # SQL schema files, auto-run on first MySQL boot
|   |-- storage/
|   |   `-- videos/              # Uploaded video files stored outside web root
|   |-- docker-compose.yml
|   |-- PHP.Dockerfile
|   |-- nginx.conf
|   `-- README.md
|-- database/
|   `-- visual_shield.sql        # Exported SQL dump (import via phpMyAdmin)
|-- docs/
|   `-- Project-Proposal-VisualShield.pdf
|-- Figma-Design/
|   |-- Visual-Shield-web-application-Entrance.png
|   |-- Visual-Shield-web-application-Login.png
|   |-- Visual-Shield-web-application-Upload-Video.png
|   `-- Visual-Shield-web-application-View-Report.png
|-- frontend/
|   |-- src/
|   |   |-- api/                 # API modules grouped by feature
|   |   |-- assets/              # Global styles
|   |   |-- components/
|   |   |   |-- atoms/
|   |   |   |-- molecules/
|   |   |   |-- organisms/
|   |   |   |-- pages/
|   |   |   `-- templates/
|   |   |-- composables/         # Shared reactive state (useAuth, useToast, useTheme, useConfig)
|   |   |-- router/              # Vue Router and auth guards
|   |   `-- utils/               # Formatting, downloads, charts, API config
|   |-- package.json
|   |-- vite.config.js
|   `-- README.md
|-- videos-to-use/               # Sample test videos
|-- AI-DISCLOUSURE.md
`-- README.md
```

---

## Database and design files

### Database folder

The `database/` folder at the root contains:

```text
database/visual_shield.sql
```

This is the exported database dump for the project. Import it through phpMyAdmin at `http://localhost:8080` to load existing users and analysis data. See the setup steps above for instructions.

### Figma design folder

The `Figma-Design/` folder contains design images for the project:

- Entrance screen
- Login screen
- Upload video screen
- View report screen

These designs were created for this project.

---

## Documentation

- [AI-DISCLOUSURE.md](./AI-DISCLOUSURE.md) - summary of how AI was used during development
- [backend/README.md](./backend/README.md) - backend-specific setup and architecture details
- [frontend/README.md](./frontend/README.md) - frontend-specific setup and structure details
- [database/visual_shield.sql](./database/visual_shield.sql) - exported database dump
- [docs/Project-Proposal-VisualShield.pdf](./docs/Project-Proposal-VisualShield.pdf) - full project proposal
- `Figma-Design/` - original design images created for this application

---

## Accessibility and scope note

Visual Shield is an accessibility-support tool, not a medical certification system.

- It helps identify visual patterns that may deserve attention
- It does not guarantee that a video is safe for every viewer
- It should be treated as a technical screening tool to support review, not replace human judgment

---

Last updated: April 2026
