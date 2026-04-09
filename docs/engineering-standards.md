# API and Web Standard Approach

This document defines a practical standard for building and reviewing features in this repository.

It is intentionally simple. The goal is consistency first, not process overhead.

---

## 1. Goals

Use this standard to:

- keep backend and frontend behavior consistent
- reduce bugs caused by duplicated logic and weak validation
- make features easier to extend as the product grows
- improve onboarding for new developers and AI agents
- create a clear definition of done for daily work

---

## 2. Current Stack

### API

- Framework: Laravel 13
- Language: PHP 8.3+
- Auth: Laravel Sanctum
- Database: SQLite locally, relational DB-friendly design
- Testing: PHPUnit
- Formatting: Laravel Pint

### Web

- Framework: Nuxt 4
- Language: TypeScript
- UI: Nuxt UI + Vuetify
- Styling: Sass + Tailwind CSS 4
- Linting: ESLint
- Package manager: pnpm

---

## 3. Backend Standard

Suggested structure:

```text
app/
  Http/
    Controllers/
    Requests/
    Resources/
  Models/
  Services/
  Policies/
  Jobs/
routes/
  api.php
tests/
  Feature/
  Unit/
```

Rules:

- Controllers orchestrate only.
- Validation belongs in Form Request classes.
- Business rules belong in Services or focused action classes.
- Response shaping belongs in API Resources.
- Multi-step writes use database transactions.
- Do not place business logic directly in routes.

---

## 4. API Response Standard

Success response:

```json
{
  "success": true,
  "message": "Operation completed.",
  "data": {}
}
```

Error response:

```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "field": ["Error message"]
  }
}
```

Rules:

- Use consistent response envelopes for API endpoints.
- Use proper HTTP status codes: `200`, `201`, `204`, `401`, `403`, `404`, `409`, `422`, `500`.
- Do not return raw exception messages to clients in production.

---

## 5. Validation and Security

- Always use Form Requests for write endpoints.
- Never trust unfiltered request payloads for business operations.
- Authorize sensitive actions with policies or gates.
- Keep secrets only in environment variables.
- Never log tokens, passwords, or sensitive personal data.
- Sanitize and validate query parameters used for filters, sorting, and pagination.

---

## 6. Data Integrity and Duplicate Protection

For critical create actions such as payments, subscriptions, and check-ins:

- support an `X-Idempotency-Key` header when duplicate writes would be costly
- combine app-level checks with database uniqueness constraints
- wrap related writes in a transaction
- prefer explicit state transitions over hidden side effects

Frontend protection helps, but the API remains the final source of truth.

---

## 7. Database and Performance

- All schema changes go through migrations.
- Add indexes for common filters, lookups, and joins.
- Prevent N+1 queries with eager loading.
- Paginate collection endpoints by default.
- Move slow or non-blocking work to queues.
- Select only fields needed for list endpoints when possible.

---

## 8. Backend Testing Minimum

Each important backend feature should include:

- feature tests for endpoint behavior
- validation tests for invalid payloads
- authorization tests where access rules exist
- duplicate submission or idempotency tests where relevant
- unit tests for core business logic

---

## 9. Frontend Standard

Suggested structure:

```text
app/
  components/
  composables/
  pages/
  layouts/
  assets/
plugins/
```

Rules:

- Pages coordinate data and layout, not heavy business logic.
- Shared data fetching belongs in composables or a small API client layer.
- Components should be reusable, focused, and easy to test.
- Keep UI state explicit: loading, empty, error, and success states must be handled.
- Prefer typed request and response shapes for API calls.
- Keep form validation close to the form and aligned with backend rules.
- Avoid duplicating permission logic across many components.

---

## 10. Frontend UX and Quality Rules

- Every async screen should show loading, error, and empty states.
- Avoid direct API calls scattered across many components.
- Keep route access and authentication checks centralized.
- Reuse design tokens and shared components before adding one-off styles.
- Ensure mobile usability for dashboard and CRUD screens.
- Prefer accessible labels, keyboard support, and clear feedback for form actions.

---

## 11. Frontend Performance Rules

- Fetch only data needed for the current screen.
- Defer heavy or secondary UI where possible.
- Avoid unnecessary reactive state duplication.
- Split large pages into smaller components when they become hard to reason about.
- Keep server/client rendering behavior predictable in Nuxt.

---

## 12. Team Rules

Must do:

- keep controllers thin
- use Form Requests for backend writes
- keep API response shapes consistent
- use transactions for multi-write backend operations
- centralize frontend data access patterns
- handle loading, error, and empty states on the web app
- run formatting, linting, and tests before merge

Avoid:

- fat controllers with embedded business logic
- inconsistent API error shapes
- silent catch blocks
- frontend-only validation for critical rules
- duplicating API call logic in multiple pages
- large page components that mix layout, data, and complex logic

---

## 13. Definition of Done

A feature is done when:

- validation and authorization are implemented where needed
- API success and error responses follow the standard
- duplicate submission is handled for critical writes
- frontend states are complete and usable
- tests cover the happy path and key edge cases
- formatting, linting, and type checks pass

---

## 14. Local Commands

### API

From `apps/api`:

```bash
composer test
php artisan test
vendor/bin/pint
```

### Web

From `apps/web`:

```bash
pnpm lint
pnpm typecheck
pnpm build
```

---

## 15. Recommended Next Improvement

This file is enough for now.

If the team wants a stronger setup later, the next practical step is:

- split this into `docs/backend-standard.md` and `docs/frontend-standard.md`
- add repository-level scripts so checks can run from the repo root
- enforce the rules in CI for pull requests
