# Visual Shield Frontend

Vue 3 single page application for uploading videos and viewing accessibility risk reports.

## Stack

- Vue 3 with Composition API and `<script setup>`
- Vite 7, Vue Router 5, Axios
- Tailwind CSS 4
- Chart.js + vue-chartjs

## Project layout

```text
frontend/
|-- src/
|   |-- api/            # API modules by feature
|   |-- assets/         # Global styles
|   |-- components/
|   |   |-- atoms/
|   |   |-- molecules/
|   |   |-- organisms/
|   |   |-- pages/
|   |   `-- templates/
|   |-- composables/    # useAuth, useToast, useTheme, useConfig
|   |-- router/         # Routes and auth guards
|   `-- utils/
|-- package.json
`-- vite.config.js
```

## Setup

Make sure the backend is running first. See [backend/README.md](../backend/README.md).

```bash
cd frontend
npm install
npm run dev
```

App runs on `http://localhost:5173`.

## Environment variables

```env
VITE_API_BASE_URL=http://localhost:8081/api
```

## Routes

- `/login`, `/register`
- `/dashboard`
- `/upload`
- `/videos/:id/report`
- `/profile`
- `/admin`

## Architecture

Follows Atomic Design: atoms, molecules, organisms, templates, and pages. Data access is in `src/api/`. Shared state is in composables. JWT tokens are stored in `localStorage` and attached automatically via Axios interceptor.
