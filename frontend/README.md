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

## Accessibility

### Semantic HTML

All components use semantic HTML5 elements rather than generic `<div>` and `<span>` tags wherever meaningful structure exists.

| Element | Used for |
|---------|----------|
| `<main>` | Primary page content wrapper (PageTemplate, AuthTemplate) |
| `<header>` | Card headers, report header, page-level action bars |
| `<footer>` | Card action rows, video player controls, profile metadata |
| `<nav>` | Desktop nav, mobile nav, user menu dropdown, pagination |
| `<article>` | VideoCard, StatCard, ChartCard, Toast notifications |
| `<section>` | StatsPanel, UploadForm, ChangePasswordForm, SegmentTable, SegmentTimeline, EmptyState, filter bars |
| `<aside>` | Inline delete-confirm prompt inside VideoCard |
| `<dialog>` | Admin modal dialogs (Create User, Reset Password) |
| `<fieldset>` + `<legend>` | Report filter button groups (type and severity) |
| `<dl>` / `<dt>` / `<dd>` | Video metadata (file size, duration, fps) in VideoCard |
| `<ul>` / `<li>` | User menu items, segment timeline legend |
| `<label>` | DropZone file input wrapper (activates file picker natively) |
| `<progress>` | Upload and processing progress bars |
| `<time>` | Video playback time display in VideoOverlay |
| `<figure>` | Data visualisation wrappers |
| `<hr>` | Visual separators in overlay toolbar |

Sort buttons in SegmentTable use a `<button>` element inside `<th>` with `aria-sort` so screen readers announce the current sort direction.

### Keyboard navigation

Every interactive element is reachable and operable by keyboard alone. Focus rings are rendered only on keyboard focus (not mouse) using the `focus-visible:` Tailwind variant so they do not appear on mouse clicks.

| Component | Focus treatment |
|-----------|----------------|
| All `<AppButton>` | `focus-visible:ring-2 focus-visible:ring-primary` |
| NavLink | `focus-visible:ring-2 focus-visible:ring-primary` |
| ThemeToggle | `focus-visible:ring-2 focus-visible:ring-accent` |
| Header links and mobile menu button | `focus-visible:ring-2`, `aria-expanded` on hamburger |
| User menu (trigger, Profile, Logout) | `focus-visible:ring-2 focus-visible:ring-primary` |
| DropZone | `focus-within:ring-2` so the entire drop zone highlights when the hidden input is focused |
| Toast dismiss button | `focus-visible:ring-2 focus-visible:ring-primary` |
| SegmentTable sort buttons | `focus-visible:ring-2 focus-visible:ring-primary` |
| Report filter buttons | `focus-visible:ring-2 focus-visible:ring-primary` |
| VideoOverlay controls and toggles | `focus-visible` outline via scoped CSS |

The pagination `<nav>` includes `aria-label="Pagination"` and a live region on the page counter. The toast container uses `aria-live="polite"` and individual toasts carry `role="alert" aria-live="assertive"`.

## Architecture

Follows Atomic Design: atoms, molecules, organisms, templates, and pages. Data access is in `src/api/`. Shared state is in composables. JWT tokens are stored in `localStorage` and attached automatically via Axios interceptor.
