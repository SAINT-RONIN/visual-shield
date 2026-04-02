# Visual Shield Frontend

The frontend is a Vue 3 single page application for uploading videos, monitoring analysis progress, and viewing accessibility risk reports.

## Stack

- Vue 3 with Composition API and `<script setup>`
- Vite
- Vue Router
- Axios
- Tailwind CSS 4
- Chart.js + `vue-chartjs`

## Main Features

- Login and registration flows
- Authenticated dashboard with video library
- Upload form with sampling rate selection
- Report page with charts, timeline, table, and exports
- Profile management
- Admin page for role-based management
- Theme support and toast notifications

## Project Layout

```text
frontend/
|-- public/
|-- src/
|   |-- api/            # API modules grouped by feature
|   |-- assets/         # Global styles
|   |-- components/
|   |   |-- atoms/
|   |   |-- molecules/
|   |   |-- organisms/
|   |   |-- pages/
|   |   `-- templates/
|   |-- composables/    # Shared reactive state and helpers
|   |-- router/         # Application routes and guards
|   `-- utils/          # Formatting, downloads, charts, config
|-- .env.example
|-- package.json
`-- vite.config.js
```

## Local Setup

### 1. Create the environment file

```powershell
Copy-Item .env.example .env
```

### 2. Install dependencies

```powershell
npm install
```

### 3. Start the development server

```powershell
npm run dev
```

The app runs on:

```text
http://localhost:5173
```

Make sure the backend API is running before using the frontend.

## Environment Variables

```env
VITE_API_BASE_URL=http://localhost:8081/api
```

The Axios instance reads this value from `src/utils/api.js`.

## Available Scripts

```powershell
npm run dev
npm run build
npm run preview
```

## Routing

Routes are defined in `src/router/index.js`.

Main screens:

- `/login`
- `/register`
- `/dashboard`
- `/upload`
- `/videos/:id/report`
- `/profile`
- `/admin`

Protected routes use auth guards before navigation.

## Architecture Notes

The frontend follows an atomic design structure.

- Atoms are small reusable UI pieces
- Molecules combine atoms into small feature blocks
- Organisms build larger sections such as charts, headers, and tables
- Templates define page layouts
- Pages fetch data and connect the route to the UI

Data access is split into feature-based modules in `src/api/`, while shared state lives in composables such as `useAuth`, `useToast`, `useTheme`, and `useConfig`.

## Auth and API Behavior

- Auth tokens are stored in `localStorage`
- The Axios interceptor attaches `Authorization: Bearer <token>` automatically
- API requests use the configured backend base URL
- Report exports are downloaded through the frontend using Blob responses

## Build Output

Production files are generated in:

```text
frontend/dist
```
