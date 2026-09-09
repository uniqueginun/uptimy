# Assessment: endpoint checking, uptime calculation, and success criteria

**Status (2026-09-09): all 10 numbered findings below, plus every item in
"Other things noticed," have been implemented.** This document is kept as
the historical record of what was found and why; it no longer describes
the current behavior of the code. See the session summary for the file-by-
file list of what changed, plus three bugs found only while implementing
the fixes (not listed below): a cross-tenant authorization gap in
`SiteEndpointController` (any logged-in user could add/delete endpoints on
someone else's site), `Site::byDomain()`'s own `REGEXP` scope had the same
MySQL-only portability problem as `Endpoint::due()` (finding #7), and
`sites.user_id` was a ULID-typed column pointed at a non-ULID `users.id`.

Scope reviewed: `app/Jobs/RunEndpointCheck.php`, `app/Models/Endpoint.php`,
`app/Models/EndpointCheck.php`, `app/Http/Resources/EndpointResource.php`,
`database/migrations/2026_09_08_171257_create_endpoint_checks_table.php`,
and the `Schedule::call(...)` in `routes/console.php`.

This is an assessment only — no application files were changed. Every claim
below was verified against the running app and its real database (via
`php artisan tinker`, `phpstan`, and a live HTTP round-trip against the dev
server), not just read from the source. Test fixtures created for
verification were deleted afterward; the app's real data (1 site, 1
endpoint) was left as found.

## How it works today

1. `routes/console.php` runs `Endpoint::due()` every minute and dispatches
   `RunEndpointCheck` for each due endpoint's id.
2. `Endpoint::scopeDue()` picks endpoints where
   `last_run_at IS NULL OR TIMESTAMPDIFF(MINUTE, last_run_at, NOW()) >= interval`.
3. `RunEndpointCheck::handle()` does `Http::timeout(10)->withoutVerifying()->connectTimeout(5)->withOptions(['http_errors' => false])->get($endpoint->fullUrl())`.
4. On no exception: `registerSuccessCheck($statusCode, $rawBody)` — always
   writes a row to `endpoint_checks`, whatever the status code.
5. On `ConnectionException`: `registerFailedCheck($e->getMessage())` writes a
   row with `http_status_code = null` and the exception message in
   `raw_response`, then rethrows.
6. "Success" is defined only at read time, by `EndpointCheck::scopeSuccessful()`:
   `http_status_code BETWEEN 200 AND 299`.
7. `Endpoint::uptimePercentage()` computes
   `successChecks_count / checks_count * 100` over **all** checks ever
   recorded for that endpoint — a lifetime average, not a rolling window.
8. `EndpointResource` exposes `frequency`, `last_check`, `last_status`,
   `uptime` to the frontend.

## Findings, most important first

### 1. Adding a new endpoint crashes the site page (confirmed, reproducible)

Reproduced live: creating an endpoint and then rendering `SiteResource`
through the same path the app uses (JSON-encoding it, as Inertia does)
throws:

```
DivisionByZeroError: Division by zero
```

from `Endpoint::uptimePercentage()`, because a brand-new endpoint has
`checks_count = 0` before its first scheduled run. Every endpoint is in
this state from the moment it's created until its first check completes
(which can be minutes away, or never, if the queue worker isn't running).
Alongside it, `EndpointResource:21` (`$this->resource->lastCheck->http_status_code`)
also emits a PHP warning for reading a property off `null`, for the same
reason — `lastCheck` legitimately has no row yet.

**Why it matters:** this isn't an edge case, it's the *normal* state of
every endpoint right after a user adds it. The "Add an endpoint" flow we
built on the show page will 500 for the user the moment they submit it,
until a check has run.

**What I'd do instead:** guard the division —
`$this->checks_count > 0 ? round($this->success_checks_count / $this->checks_count * 100, 2) : null`
— and let the frontend render "not checked yet" for a `null` uptime,
same as it already does for `last_check`/`last_status`.

### 2. Success is HTTP-status-only, and TLS verification is explicitly disabled

`scopeSuccessful()` treats any `2xx` as success and anything else (or a
connection failure) as not. Two consequences worth naming explicitly:

- **No content check.** A `200` from a CDN/hosting "site suspended" or
  "under maintenance" page, or a login-walled page, counts as "up." The
  target being reachable and returning 2xx is a real signal, but it's a
  different (weaker) claim than "the site is working," which is usually
  what "uptime" implies to a user.
- **`withoutVerifying()` disables TLS certificate checks.** An expired,
  self-signed, or mismatched certificate — one of the most common ways a
  real site actually "goes down" for real visitors, since browsers refuse
  the connection — will still be reported as **up** here, because the
  check never validates the certificate. This is the single most
  surprising gap for a tool whose whole purpose is "tell me when my site
  is broken."

**What I'd do instead:** re-enable certificate verification by default
(only disable it per-endpoint if a user explicitly opts in for
self-signed/internal services), and consider treating redirects to an
unexpected host, or an optional "must contain this text" assertion, as
part of what "successful" means — even a simple opt-in keyword check
would catch a large class of false "up" results that status-code-only
checks miss.

### 3. The observed status distribution is a live example of why this matters

The one real endpoint being monitored (`/register` on the seeded site) has
79 recorded checks; the vast majority (55 of the first 61 sampled) are
`404`, only a handful are `200`. Lifetime uptime is sitting around 8–10%.
Whether that's a real problem with the monitored route or a mismatch
between the endpoint's configured `uri` and what's actually being hit
(see #4), it's a concrete illustration of how directly the "success"
definition drives the number a user will see and trust.

### 4. No validation on the endpoint URL, and naive concatenation to build it

`SiteEndpointController::store()` does
`$site->endpoints()->create($request->only('uri', 'interval'))` with no
`validate()` call at all — `uri` can be empty, missing a leading `/`, or
even a full URL. `Endpoint::fullUrl()` then does plain string
concatenation: `site.url . uri`. If `uri` doesn't start with `/`, or the
site URL already ends with one, the resulting URL is wrong — and a wrong
URL produces a check result that says nothing about the actual site's
uptime, just about our own URL construction. This directly poisons the
number from #2/#3: a "down" reading here could mean the monitored path is
broken, or it could just mean the request never pointed at the right
place.

**What I'd do instead:** validate `uri` (e.g. `required|string|starts_with:/`
or normalize it server-side before concatenating), and build the final URL
with something that understands URL joining (e.g. rtrim the base, ltrim
the path, join with a single `/`) instead of raw string append.

### 5. Lifetime average, not a time window

`uptime_percentage` divides all-time successes by all-time checks, with no
decay and no window (e.g. last 24h/7d). Two failure modes fall out of
this:

- A site that's been solid for a year and goes down right now will still
  show a reassuring ~99%+ "uptime" for a long time after the outage
  started — exactly when the number matters most, it lags the worst.
- Conversely a rocky first day (endpoint just added, still being tuned)
  permanently drags the number down even after the site is stable, unless
  history is manually pruned.

**What I'd do instead:** this is a product decision as much as a technical
one, so worth deciding explicitly rather than by default — but the common
pattern is to compute uptime over a rolling window (e.g. last 24h and last
30d, shown separately) rather than — or in addition to — the lifetime
average, precisely so a current outage is visible immediately instead of
being diluted by months of history.

### 6. `uptime_percentage` is an N+1 the moment a site has more than one endpoint

The accessor calls `$this->loadCount(['successChecks', 'checks'])` on
`$this` — i.e., once per endpoint instance, at read time. Verified live: a
site with 3 endpoints triggers one query to load the site, one for the
endpoints, one for the batched `lastCheck` relation (that part is fine,
it's a proper `latestOfMany()` eager load) — and then, once JSON
serialization actually walks into each `EndpointResource` and touches
`uptime_percentage`, each endpoint fires its own pair of `COUNT` queries
against `endpoint_checks`, a table that only grows and is never pruned
(every check's full response body is stored — see #8). A site with 20
endpoints means 20 extra count-query round trips on every page load, and
they get more expensive over time as history accumulates.

**What I'd do instead:** compute `checks_count`/`success_checks_count` with
`Endpoint::withCount([...])` once, at the query that loads the endpoints
for the page (in `SiteController::show`/`index`), instead of lazily inside
the accessor per-instance.

### 7. `Due` scope only works on MySQL — and can't run under this project's own test suite

`scopeDue()` uses raw SQL: `TIMESTAMPDIFF(MINUTE, last_run_at, NOW()) >= \`interval\``.
`TIMESTAMPDIFF` is MySQL-specific. Verified directly: running the same
raw clause against SQLite (the connection `phpunit.xml` configures for the
test suite: `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`) throws
immediately (`no such column: MINUTE`). Consequence: nobody can write a
Pest/PHPUnit test for "which endpoints are due" against this project's own
default test database. That's very likely *why* there are currently zero
tests for any of this (`RunEndpointCheck`, `Endpoint::due()`,
`uptime_percentage`, success/failure recording) — the scope as written
resists being tested at all in this project's normal test setup.

Separately: `TIMESTAMPDIFF(MINUTE, …)` truncates to whole minutes, so an
endpoint checked at `12:00:59` can be picked up again at `12:01:01` — 2
seconds later, not a full interval later. For a 1-minute interval that's
mostly cosmetic; for longer intervals it means the "due" boundary is fuzzy
by up to 59 seconds in either direction.

**What I'd do instead:** express "due" in PHP/query-builder terms
(`where('last_run_at', '<=', now()->subMinutes(...))` per interval bucket,
or a database-agnostic expression) so it's portable and testable against
SQLite too, matching how the rest of the app's scopes (e.g.
`Site::scopeByDomain`) are written to at least be exercised by the test
suite (even `byDomain`'s `REGEXP` is MySQL-only, but that one isn't
currently blocking test coverage the way `Due` is).

### 8. Full response bodies stored forever, with no distinction between "the page's real content" and "our own error message"

Every check — success or failure — stores the entire raw body/exception
message in one `text` column (`raw_response`), forever, with no retention
policy. For a 1-minute-interval endpoint returning an 8KB page, that's
roughly 11MB/day, ~4GB/year, per endpoint, and it never gets smaller. On
top of the volume, a failure's `raw_response` holds our own client
exception message (e.g. a cURL error string), while a success's holds the
actual remote page — so a person reading that column later can't tell
which kind of content they're looking at without cross-referencing
`http_status_code`.

**What I'd do instead:** separate the concerns — a `status` outcome
(`up`/`down`/`error`) plus a distinct `error_message` for failures — and
either drop `raw_response` for successful checks (you rarely need the full
HTML of a 200) or cap/truncate what's stored and add a retention job that
prunes old rows.

### 9. Formatting logic lives on the model's raw column, which is also read by raw SQL

`Endpoint::interval()` overrides the raw `interval` attribute to return a
formatted string (`"Every 15 Minutes"`) instead of the integer stored in
the column. Confirmed live: `EndpointResource`'s `frequency` field is
therefore the string `"Every Minute"`, not a number — despite the same
integer column being read directly, unformatted, by the raw SQL in
`scopeDue()` (which has to backtick-quote `` `interval` `` specifically
because it collides with a SQL reserved word). Two different parts of the
codebase now disagree about what `interval` "is" — a formatted phrase in
PHP-land, a raw integer in the one place that actually schedules checks.
Any future code that does `$endpoint->interval` for a calculation (rather
than display) will silently operate on a string.

**What I'd do instead:** keep the model attribute as the raw integer, and
move the "Every N Minutes" phrasing into the resource/frontend
presentation layer, where formatting decisions belong.

### 10. Only `ConnectionException` is caught; the failure path double-reports via the queue's own failure tracking

`handle()` catches `ConnectionException` (confirmed live: DNS failures and
read/connect timeouts both surface as this class, so the common failure
paths are covered), records a check row, and then **rethrows** the same
exception. With the job's default `$tries` (none set → 1), that rethrow
also lands the job in Laravel's own `failed_jobs` table — so every network
failure is recorded twice, in two different systems, with two different
shapes. Any exception that isn't a `ConnectionException` (e.g. a
mid-transfer failure, redirect loop) isn't caught at all: no
`endpoint_checks` row gets written, so that attempt vanishes from the
uptime calculation entirely rather than counting as a failure.

**What I'd do instead:** decide whether the queue's failure tracking or
the app's own `endpoint_checks` table is the source of truth for
"this check errored," and catch broadly enough (or use a `finally`) that
every attempt — whatever went wrong — produces exactly one row, so the
denominator in the uptime calculation always matches "how many times we
actually tried."

## Other things noticed in passing (lower priority)

- `phpstan` (the project's own configured level, 7) currently reports 18
  errors across these new files, mostly missing return types on relation
  methods (`site()`, `checks()`, `successChecks()`, `lastCheck()`,
  `endpoint()`) — every other model in the app (`Site`) declares these
  (`: BelongsTo`, `: HasMany`), so this is a drop in consistency as much
  as a static-analysis nit.
- The `endpoint_checks.http_status_code` migration declares
  `tinyInteger()`, which is a **signed** column (range −128..127) and
  cannot hold real HTTP status codes like `200`, `404`, or `500`. The
  live database's actual column is `int`, not `tinyint` — so whatever is
  currently running has already drifted from what the migration file
  would produce on a fresh `migrate`. Worth reconciling one way or the
  other before someone else provisions a new environment from this
  migration and can't insert a single real check result.
- `Schedule::call(...)->withoutOverlapping()` guards the *scheduler tick*,
  not the individual endpoint. Nothing stops the same endpoint being
  dispatched again next minute if its previous job is still sitting in
  the queue (nothing updates `last_run_at` until the job actually runs) —
  under a backlog, the same endpoint can queue up multiple pending checks
  at once rather than naturally spacing out.
- Zero test coverage exists for any of this (`RunEndpointCheck`, the
  `Endpoint`/`EndpointCheck` models, `uptime_percentage`, the `due`
  scope) — confirmed by searching `tests/` for any reference. Given #1 and
  #7 above, that's not surprising: the crash-on-fresh-endpoint bug and the
  SQLite-incompatible scope are exactly the kind of thing a test would
  have caught immediately, and the scope's current form actively resists
  being tested in this project's own suite.

## If I had to pick three to fix first

1. **#1** — it's a live crash on the normal, everyday path (add an
   endpoint → view the site).
2. **#2** — `withoutVerifying()` plus status-code-only success quietly
   redefines "uptime" to exclude one of the most common real outages
   (bad/expired TLS), which seems worth a deliberate decision rather than
   a side effect of a Guzzle option.
3. **#7** — making `due` portable/testable unblocks writing tests for
   everything else in this list, and is probably why none exist yet.
