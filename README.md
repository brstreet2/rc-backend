# Promo & Scheduling Engine API

This project is a backend API for a Promotion & Scheduling Engine. The core objective is to return one deterministic active promotion for each audio based on business validity rules, scope specificity, and tie-break conflict resolution. The API is designed so that the same input conditions always produce the same winning promotion, while still supporting fallback behavior when no promotion is valid.

The implementation uses Laravel 12 with PostgreSQL for the main runtime and SQLite in-memory for automated tests.

## Quick Setup
Install dependencies and initialize environment:
```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure PostgreSQL in `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=rc_backend
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

Run migrations and seeders:
```bash
php artisan migrate:fresh --seed
```

Start the API server:
```bash
php artisan serve
```

Base URL: `http://127.0.0.1:8000`

## Test Setup
Testing uses `.env.testing` with an in-memory SQLite database, so tests are isolated and fast:
```bash
php artisan test
```

## Postman Collection
A ready-to-import Postman collection is included in this repository:

- `postman/rc-backend.postman_collection.json`

You can import this file directly into Postman and run requests against `http://127.0.0.1:8000`.
Some requests already include saved example/preview responses to make behavior verification faster.

## Core Business Rules
The winner selection follows four strict rule layers. First, a promotion is considered only if it is visible, within active time range, and not soft-deleted. Next, scope specificity is prioritized from exact match (`network_id`, `mformat`, `channel_id`) down to global. If multiple candidates still remain in the same scope level, conflict resolution applies in order: highest priority, then highest version, then newest `created_at`. If no candidate survives these rules, the API returns `promo = null` for that audio.

## Main Endpoints
The required API surface is implemented through paginated audio listing, active audio listing, and promotion CRUD:

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/audios` | Paginated audio list |
| `GET` | `/api/audios/active` | Paginated audio list with computed active promotion |
| `POST` | `/api/promotions` | Create promotion |
| `PUT` | `/api/promotions/{promotion}` | Update promotion |
| `DELETE` | `/api/promotions/{promotion}` | Soft delete promotion |

## Extra Features (Separated from Main)
The extra features are intentionally separated from the main requirement and focus on operational decision support:

1. Promotion simulation time (`GET /api/audios/active?at=...`) was added so teams can evaluate “what would be active” at a specific timestamp without changing live data.

2. Dry-run preview (`POST /api/promotions/preview`) was added to validate business impact before writing data. It answers whether a candidate promotion would win or lose under current rules.

3. Audio schedule timeline (`GET /api/audios/{audio}/schedule?from=...&to=...`) was added to visualize winner transitions over a time window. This is useful for planning and debugging promotion sequencing.

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/audios/active?at=...` | Simulate active promotion at a specific timestamp |
| `POST` | `/api/promotions/preview` | Dry-run candidate promotion outcome (win/lose) |
| `GET` | `/api/audios/{audio}/schedule?from=...&to=...` | Timeline of winner transitions |

## Database Schema (Implemented)
### `audio`
| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | bigint | No | Primary key |
| `title` | varchar | No | Audio title |
| `network_id` | bigint | Yes | Scope dimension |
| `mformat` | varchar | Yes | Scope dimension |
| `channel_id` | bigint | Yes | Scope dimension |
| `created_at` | timestamp | Yes | Laravel timestamp |
| `updated_at` | timestamp | Yes | Laravel timestamp |
| `deleted_at` | timestamp | Yes | Soft delete column |

Indexes:
- scope index on `(network_id, mformat, channel_id)`
- index on `deleted_at`

### `promotions`
| Column | Type | Nullable | Notes |
|---|---|---|---|
| `id` | bigint | No | Primary key |
| `audio_id` | bigint | No | FK to `audio.id` |
| `network_id` | bigint | Yes | Scope dimension |
| `mformat` | varchar | Yes | Scope dimension |
| `channel_id` | bigint | Yes | Scope dimension |
| `priority` | int | No | Conflict rank (higher wins) |
| `version` | int | No | Tie-break rank (higher wins) |
| `visible` | boolean | No | Validity flag |
| `start_at` | timestamp | No | Validity start |
| `end_at` | timestamp | No | Validity end |
| `created_at` | timestamp | Yes | Laravel timestamp / tie-break |
| `updated_at` | timestamp | Yes | Laravel timestamp |
| `deleted_at` | timestamp | Yes | Soft delete column |

Indexes:
- active lookup index on `(audio_id, visible, start_at, end_at)`
- scope index on `(network_id, mformat, channel_id)`
- conflict index on `(priority, version, created_at)`
- index on `deleted_at`

## Indexing Rationale
The table fields are mostly requirement-driven, but the indexes are performance-driven based on query patterns used by this project:

- `audio(network_id, mformat, channel_id)` helps scope-aware matching by reducing search cost when promotions are evaluated against audio scope dimensions.
- `audio(deleted_at)` helps list endpoints skip soft-deleted rows without full scans.
- `promotions(audio_id, visible, start_at, end_at)` helps active-promotion lookup quickly narrow candidates per audio before applying conflict logic.
- `promotions(network_id, mformat, channel_id)` helps scope-specific filtering (`exact`, `network+mformat`, `network-only`, `global`).
- `promotions(priority, version, created_at)` helps deterministic tie-break sorting when scope level is the same.
- `promotions(deleted_at)` helps active and preview queries exclude soft-deleted promotions efficiently.

## Test Coverage Highlights
The test suite is controller-focused and covers both core and extra behavior. Core tests verify scope hierarchy correctness, tie-break ordering, validity filtering, and fallback handling. Additional tests cover promotion CRUD validation/behavior, simulation (`at`), preview decisions, and schedule timeline transitions.

## Requirement-to-Test Traceability
| Feature | Requirement Summary | Test Location |
|---|---|---|
| Audio List | Menampilkan daftar audio | `tests/Feature/Api/AudioControllerTest.php` → `test_index_returns_paginated_audio_list` |
| Promo Validity | Promo aktif berdasarkan `visible`, waktu, dan non-soft-deleted | `tests/Feature/Api/AudioControllerTest.php` → `test_active_returns_null_when_only_hidden_expired_or_deleted_promotions_exist` |
| Scope Priority | Scope lebih spesifik selalu menang | `tests/Feature/Api/AudioControllerTest.php` → `test_scope_hierarchy_exact_scope_beats_less_specific_scopes` and `test_scope_hierarchy_network_mformat_null_channel_beats_network_only` |
| Conflict Resolution | Tie-break dengan `priority`, `version`, `created_at` | `tests/Feature/Api/AudioControllerTest.php` → `test_conflict_resolution_prefers_higher_priority_within_same_scope`, `test_conflict_resolution_prefers_higher_version_when_priority_is_equal`, `test_conflict_resolution_prefers_newer_created_at_when_priority_and_version_are_equal` |
| Fallback | Audio tanpa promo valid tetap ditampilkan (`promo = null`) | `tests/Feature/Api/AudioControllerTest.php` → `test_active_returns_null_when_only_hidden_expired_or_deleted_promotions_exist` |

### Extra Feature Coverage
| Extra Feature | Test Location |
|---|---|
| Promotion simulation (`at`) | `tests/Feature/Api/AudioControllerTest.php` → `test_active_supports_simulation_at_parameter` |
| Dry-run preview | `tests/Feature/Api/PromotionControllerTest.php` → `test_preview_returns_would_win_true_for_better_candidate`, `test_preview_returns_would_win_false_when_candidate_loses`, `test_preview_returns_candidate_not_active_reason_when_time_window_invalid` |
| Audio schedule timeline | `tests/Feature/Api/AudioControllerTest.php` → `test_schedule_returns_segments_with_winners_and_message` |
