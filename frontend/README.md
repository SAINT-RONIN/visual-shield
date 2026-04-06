# Visual Shield Frontend

The frontend is a Vue 3 single page application for uploading videos, monitoring analysis progress, and viewing accessibility risk reports.

## Stack

- Vue 3 with Composition API and `<script setup>`
- Vite 7
- Vue Router 5
- Axios
- Tailwind CSS 4
- Chart.js + vue-chartjs

## Main features

- Login and registration flows
- Authenticated dashboard with video library
- Upload form with sampling rate selection
- Report page with charts, timeline, table, and exports
- Profile management
- Admin page for role-based user management
- Theme support and toast notifications

## Project layout

```text
frontend/
|-- public/
|-- src/
|   |-- api/            # API modules grouped by feature (auth, users, videos, admin, config)
|   |-- assets/         # Global styles
|   |-- components/
|   |   |-- atoms/      # Small reusable UI primitives
|   |   |-- molecules/  # Small feature blocks combining atoms
|   |   |-- organisms/  # Larger sections (charts, headers, tables)
|   |   |-- pages/      # Page-level components
|   |   `-- templates/  # Page layout templates
|   |-- composables/    # Shared reactive state (useAuth, useToast, useTheme, useConfig)
|   |-- router/         # Vue Router routes and auth guards
|   `-- utils/          # Formatting, downloads, charts, API config
|-- .env.example
|-- package.json
`-- vite.config.js
```

## Local setup

Make sure the backend is running before starting the frontend. See [backend/README.md](../backend/README.md) for backend setup instructions.

### Step 1 - Create the environment file

```bash
cp .env.example .env
```

### Step 2 - Install dependencies and start the dev server

```bash
cd frontend
npm install
npm run dev
```

The app runs on:

```text
http://localhost:5173
```

## Environment variables

```env
VITE_API_BASE_URL=http://localhost:8081/api
```

The Axios instance reads this value from `src/utils/api.js`.

## Available scripts

```bash
npm run dev       # Start development server
npm run build     # Build for production
npm run preview   # Preview the production build
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

## Architecture notes

The frontend follows an atomic design structure:

- Atoms are small reusable UI pieces (buttons, badges, inputs)
- Molecules combine atoms into small feature blocks
- Organisms build larger sections such as charts, headers, and tables
- Templates define page layouts
- Pages fetch data and connect the route to the UI

Data access is split into feature-based modules in `src/api/`. Shared state lives in composables such as `useAuth`, `useToast`, `useTheme`, and `useConfig`.

## Auth and API behavior

- JWT access tokens and the current user payload are stored in `localStorage`
- The Axios interceptor attaches `Authorization: Bearer <jwt>` automatically to every request
- Report exports are downloaded through the frontend using Blob responses

## Build output

Production files are generated in:

```text
frontend/dist/
```
