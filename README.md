# Visual Shield

An Automated Video Accessibility Risk Analysis Web Application

| Field | Value |
|-------|-------|
| Project | Visual Shield |
| Type | Full-stack web application |
| Backend | PHP 8.4, MySQL, Docker Compose |
| Frontend | Vue 3, Vite, Tailwind CSS 4 |
| Focus | Flash, luminance, and motion risk analysis for video |

---

## About this project

Visual Shield is a web application that lets users upload videos and receive automated accessibility-focused reports about visual triggers that may affect viewers with photosensitive conditions.

The system analyses rapid flashing, luminance changes, and strong motion intensity in uploaded video files. After processing, the app presents an interactive report with charts, flagged segments, exports, and streaming support so the user can review the video alongside the analysis results.

---

IMPORTANT NOTE: THIS APP IS SUBJECT TO EXPANSION AND CHANGES IN THE FUTURE.

Visual Shield does not provide medical advice or medical diagnosis. The analysis is automated and based on technical thresholds for accessibility awareness only.

Additional information can be found in [AI-USAGE.md](./AI-USAGE.md), [backend/README.md](./backend/README.md), and [frontend/README.md](./frontend/README.md).

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
| Web server | Nginx |
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

1. Clone or unzip the project folder
2. Open a terminal in the `backend` folder
3. Run:

```powershell
docker compose up -d --build
```

4. Open a second terminal in the `frontend` folder
5. Run:

```powershell
Copy-Item .env.example .env
npm install
npm run dev
```

6. Open your browser and go to `http://localhost:5173`
7. Register a new account through the frontend
8. Upload a video and wait for the background worker to finish processing it

### Important setup note

The backend has been simplified so that:

- `docker compose up -d --build` starts the backend containers
- Composer dependencies are installed automatically inside the backend app
- MySQL tables are initialized automatically from the migration files on the first database boot

So for the backend, there is no separate `composer install` step anymore and no manual first-time SQL import step anymore.

The frontend is still a separate Vite application, so it still needs:

- `npm install`
- `npm run dev`

### Optional database import

If you want to inspect or import the exported database, there is also a root-level SQL dump at:

```text
database/visual_shield.sql
```

This is optional. The normal backend startup already creates the tables automatically from the backend migration files on first boot.

You would use the SQL dump only if you specifically want the exported database contents instead of starting from a clean local database.

### Backend URLs

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
| `php` | internal | Runs the PHP application |
| `worker` | internal | Processes queued video analysis jobs |
| `mysql` | `3306` by default | Database server |
| `phpmyadmin` | `8080` | Database management UI |

### Environment files

Backend defaults already work for local development, so `backend/.env` is optional unless you want to override ports or credentials.

Default backend values are:

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

Frontend default value is:

```env
VITE_API_BASE_URL=http://localhost:8081/api
```

### Common commands

```powershell
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
| Backend containers start but the database looks old | Run `docker compose down -v` inside `backend/`, then run `docker compose up -d --build` again |
| Frontend cannot connect to the API | Check that the backend is running and that `VITE_API_BASE_URL` points to `http://localhost:8081/api` |
| Port already in use | Change the port in `backend/.env` or stop the conflicting local service |
| Video analysis is not progressing | Check `docker compose logs -f worker` in the `backend/` folder |
| `npm install` fails | Make sure you are using a supported Node.js version |

---

## Accounts and testing

By default, the normal backend startup flow gives you a clean local database, so the simplest testing path is:

- Register a new account from the frontend
- New users are created with the `viewer` role by default
- If you want to test the admin area, you can promote a user to `admin`
- The `videos-to-use/` folder contains some default videos for quick and easy access during testing

Test users:

| Role | Username | Password |
|------|----------|----------|
| Viewer | `TestUser1` | `Password123!` |
| Admin | `Admin` | `Admin123!` |

There is also an exported database file in:

```text
database/visual_shield.sql
```

That export contains existing records, including user rows and analysis data. If you import that file, your database will no longer be a clean empty setup.

For example, after registering a user, you can update their role in phpMyAdmin or run a query like this on the `visual_shield` database:

```sql
UPDATE users
SET role = 'admin'
WHERE username = 'your_username';
```

Default phpMyAdmin access for local development is:

| Field | Value |
|-------|-------|
| URL | `http://localhost:8080` |
| Username | `root` |
| Password | `root` |

---

## How the analysis works

Here is the simplified workflow used by Visual Shield:

1. A user uploads a video and selects a sampling rate
2. The backend stores the file safely and creates a queued job
3. The worker extracts frames using FFmpeg
4. The analysis logic compares frames to calculate flash and motion metrics
5. Results are saved in MySQL as analysis results, flagged segments, and datapoints
6. The frontend displays the report with charts, segments, and exports

The current backend configuration includes:

| Parameter | Value | Description |
|-----------|-------|-------------|
| Allowed sampling rates | `10, 15, 30, 60` fps | User-selectable analysis rates |
| Max file size | `500 MB` | Maximum upload size |
| Max total frames | `10000` | Cap on processed frames per video |
| Flash threshold | `20` | Minimum luminance delta to count as a flash |
| Flash danger frequency | `3/sec` | Warning threshold for dangerous flash frequency |
| Motion threshold | `30` | Minimum pixel difference to count as motion |
| Motion severity medium | `60` | Medium motion intensity threshold |
| Motion severity high | `120` | High motion intensity threshold |

---

## Database and design files

I added two root-level folders to make supporting material easier to find.

### Database folder

The `database/` folder contains:

```text
database/visual_shield.sql
```

This is the exported database file for the project. It can be used if someone wants to inspect the database structure and data directly or import that exported state into MySQL through phpMyAdmin.

### Figma design folder

The `Figma-Design/` folder contains the design images for the project, including screens such as:

- entrance
- login
- upload video
- view report

These designs were created by me for this project. They were not taken from the internet or copied from another source.

---

## Project structure

```text
visual-shield/
|-- backend/
|   |-- app/
|   |   |-- public/              # API entry point and routes
|   |   |-- cli/                 # Background worker
|   |   |-- src/
|   |   |   |-- Controllers/
|   |   |   |-- Services/
|   |   |   |-- Repositories/
|   |   |   |-- Models/
|   |   |   |-- DTOs/
|   |   |   |-- Config/
|   |   |   |-- Framework/
|   |   |   `-- Utils/
|   |-- database/migrations/     # SQL schema initialization files
|   |-- docker-compose.yml
|   |-- PHP.Dockerfile
|   `-- README.md
|-- database/
|   `-- visual_shield.sql        # Exported SQL database file
|-- Figma-Design/
|   |-- Visual-Shield-web-application-Entrance.png
|   |-- Visual-Shield-web-application-Login.png
|   |-- Visual-Shield-web-application-Upload-Video.png
|   `-- Visual-Shield-web-application-View-Report.png
|-- frontend/
|   |-- src/
|   |   |-- api/
|   |   |-- assets/
|   |   |-- components/
|   |   |-- composables/
|   |   |-- router/
|   |   `-- utils/
|   |-- package.json
|   `-- README.md
|-- AI-USAGE.md
`-- README.md
```

---

## Documentation

Useful files in this repository:

- [AI-USAGE.md](./AI-USAGE.md) for a summary of how AI was used during development
- [backend/README.md](./backend/README.md) for backend-specific setup and architecture details
- [database/visual_shield.sql](./database/visual_shield.sql) for the exported database
- [frontend/README.md](./frontend/README.md) for frontend-specific setup and structure details
- `Figma-Design/` for the original project design images created for this application

---

## Accessibility and scope note

Visual Shield is an accessibility-support tool, not a medical certification system.

- It helps identify visual patterns that may deserve attention
- It does not guarantee that a video is safe for every viewer
- It should be treated as a technical screening tool to support review, not replace human judgment

---

Last updated: April 2026
