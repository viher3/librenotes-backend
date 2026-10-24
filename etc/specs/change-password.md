# LibreNotes — Change Own Password (Backend Spec)

> **Status: implemented** (see §9 for where the implementation deviates from the design below; §9 is authoritative). All open questions of the first draft are resolved (§8).

Lets an authenticated user change **their own** password by providing the current one. Follows `etc/docs/*` (DDD layout, VOs for primitives, one `exceptions()` map per controller, OpenAPI under `etc/openapi/user/`).

## 1. Current state (`src/User`)

A change-password flow already exists but is not safe or usable as a self-service feature:

| Piece | Today | Problem |
| ----- | ----- | ------- |
| Route | `PATCH /users/{id}/change-password` (`ChangeUserPasswordController`) | The `id` comes from the URL; nothing checks that it is the authenticated user, so any logged-in user could target another user's password. **This route is removed** (§2). |
| Validation schema | 8–20 chars | Limits did not match the `Password` VO (5–20). (`"type": "array"` is the project's convention: `RequestParamsJsonValidator` treats it as an object when `properties` is present, so it was not a bug.) |
| Body casing | `currentPassword`, `newPassword` | `etc/docs/Api.md` requires snake_case. |
| `ChangePasswordService` | `if ($currentPassword && !validate(...))` | An empty/`null` current password **skips** the check. The `Password` VO is not used, so the new password is never validated by the domain. |
| Handler | Wraps every non-`InvalidUserPassword` exception in `ErrorUserChangePassword` (500) | `NotFoundUser` becomes a 500. `ErrorUserChangePassword` is a `DomainError` but represents an infrastructure failure. |
| Sessions | `User::increaseTokenVersion()` exists but is never called nor checked | Old JWTs stay valid after a password change. |
| Tests | Unit test for the handler only | No acceptance test. |

## 2. Target behaviour

1. `PATCH /users/{id}/change-password` is **deleted** (route, controller, OpenAPI path and schema). There is no admin variant: the only way to change a password is the owner doing it with their current password. (The `TODO` about ADMIN in `ChangePasswordHandler` goes away with it.)
2. `PATCH /me/password` changes the password of the **authenticated** user (id taken from the token, never from the URL).
3. The current password is always verified.
4. The new password must satisfy the `Password` VO rules (**8–44 characters**) and differ from the current one.
5. Failed current-password attempts are rate limited: **3 attempts**, then `429` (§4.5).
6. On success the stored hash is replaced, `passwordRecoveryRequestedAt` is cleared (already done by `User::changePassword`), `tokenVersion` is increased so previously issued tokens stop working (§5), and a confirmation email is sent (§4.6).
7. The endpoint returns `204 No Content`; the client must log in again.

## 3. API

### `PATCH /me/password`

Auth: Bearer JWT.

Request (snake_case):

```json
{
  "current_password": "string",
  "new_password": "string"
}
```

Validation (inline JSON Schema, root `"type": "object"`): both fields required, strings, `additionalProperties: false`. `new_password`: `minLength` 8, `maxLength` 44. `current_password`: `minLength` 1, `maxLength` 44 (accounts created under the old 5–20 rule must still be able to authenticate with their current password).

Responses:

| HTTP | Error code | When |
| ---- | ---------- | ---- |
| 204 | — | Password changed |
| 400 | existing schema-validation response | Missing field, wrong type, `new_password` length out of 8–44 |
| 401 | — | Missing/invalid/expired token (firewall) |
| 404 | `user.not_found` | Token refers to a user that no longer exists |
| 422 | `user.invalid_password` | `current_password` does not match (counts as a failed attempt) |
| 422 | `user.password_unchanged` | `new_password` equals `current_password` |
| 422 | `user.invalid_new_password` | `new_password` breaks the VO rules |
| 429 | `user.change_password_rate_limited` | 3 failed attempts in the window; `Retry-After` header and `params.retry_after` (seconds) |
| 500 | `user.change_password_error` | Unexpected infrastructure failure |

Domain errors use the existing body shape (`code`, `message`, `params`, `status`); schema-validation failures return `{ "errors": [...] }`. `user.invalid_password` moves from 400 to 422.

## 4. Implementation design

### 4.1 Domain (`src/User/Domain`)

- `Model/User::changePassword(HashedPassword)`: also calls `increaseTokenVersion()` and records a `UserPasswordChanged` event, so the invariants live in the aggregate.
- `Event/UserPasswordChanged` (carries user id and email).
- `Service/ChangePasswordService`:
  - `$currentPassword` is **required** (non-nullable); drop the `if ($currentPassword && ...)` bypass.
  - Build the new password through the `Password` VO; update its bounds to 8–44.
  - Reject `new == current` with `PasswordUnchanged`.
  - Remove the now-unused `validateUserIdCurrentPassword` if nothing else calls it.
- `Exception/`:
  - `InvalidUserPassword` — keep (`user.invalid_password`), only for a wrong current password.
  - `InvalidNewPassword` — new (`user.invalid_new_password`), thrown by the `Password` VO for length violations (its current message is hard-coded in Spanish; use an English message plus `params` `min`/`max`).
  - `PasswordUnchanged` — new (`user.password_unchanged`).
  - `ChangePasswordRateLimited` — new (`user.change_password_rate_limited`, `params`: `retry_after`).
  - `ErrorUserChangePassword` — make it extend `CriticalException`.
- `Service/ChangePasswordAttemptLimiter` (interface in Domain, implementation in Infrastructure): `assertAllowed(UserId)`, `registerFailure(UserId)`, `reset(UserId)`.

### 4.2 Application (`src/User/Application/ChangePassword`)

- `ChangePasswordCommand`: `userId`, `currentPassword`, `newPassword` (rename `oldPassword`).
- `ChangePasswordHandler` (`TransactionalCommandHandler`):
  1. `limiter->assertAllowed(userId)`.
  2. Call the service; on `InvalidUserPassword` → `limiter->registerFailure(userId)` and rethrow.
  3. On success → `limiter->reset(userId)`.
  4. No blanket `catch (\Exception)`; domain errors propagate. Unexpected throwables may be wrapped in `ErrorUserChangePassword`.
- Remove `ResetUserPasswordCommand` ideas from the earlier draft — no admin path.

### 4.3 Infrastructure (`src/User/Infrastructure`)

- Delete `Ui/Controllers/ChangeUserPasswordController.php` and the `change_password_user` route.
- New `Ui/Controllers/ChangeCurrentUserPasswordController` extending `BaseApiController`; id from the authenticated user, schema above, `dispatchTransactional(...)`, returns `204`.
- `exceptions()`: `InvalidUserPassword`, `PasswordUnchanged`, `InvalidNewPassword` → 422; `NotFoundUser` → 404; `ChangePasswordRateLimited` → 429; `ErrorUserChangePassword` → 500.
- Route in `routing.yaml`:

  ```yaml
  change_current_user_password:
    path: /me/password
    methods: PATCH
    controller: App\User\Infrastructure\Ui\Controllers\ChangeCurrentUserPasswordController
  ```

### 4.4 OpenAPI (`etc/openapi/user/`)

- `paths.yaml`: remove `/users/{id}/change-password`; add `/me/password` (`patch`, Bearer, tag `User`, responses from §3 including `429` with the `Retry-After` header).
- `schemas.yaml`: remove `ChangeUserPasswordRequest`; add `ChangeCurrentUserPasswordRequest` (`current_password`, `new_password`, with the length limits).
- The frontend client is generated from `/api/doc.json`; regenerate it after merging.

### 4.5 Rate limiting

- Symfony `rate_limiter` in `config/packages/rate_limiter.yaml`: `change_password_attempts`, policy `fixed_window`, `limit: 3`, `interval: '15 minutes'`, keyed by **user id** (not IP, so a stolen token cannot be used to brute-force from many IPs, and a shared IP does not lock other users).
- Only **failed** current-password checks consume a token (`registerFailure`); a success resets the counter. Locking applies even if the correct password is sent while locked.
- Implementation `Infrastructure/RateLimiter/SymfonyChangePasswordAttemptLimiter` wraps `RateLimiterFactory`. Use a shared cache pool (not per-process) so it works across php-fpm workers.
- The window (15 min) is a default; adjust in config only.

### 4.6 Email notification

- `UserPasswordChanged` is dispatched through the existing `event.bus` after the command.
- New `Domain/EventHandler/UserPasswordChangedEventHandler` (pattern of `UserCreatedEventHandler`) calls `UserNotifier` to send a "your password was changed" email using a new template in the `Notification` module (locale-aware via `User.locale`). Body must **not** include any password data; include timestamp and a hint to contact support / recover the account if it was not them.
- Email failures must not roll back or fail the password change (handler catches and logs).
- Respect the existing mail-enabled flag used by signup activation emails.

## 5. Session invalidation

`User` already has `tokenVersion` (persisted, starts at 1) but nothing reads it.

1. Include `token_version` as a claim when the Security module issues a JWT (`POST /login`, `PUT /token-renew`).
2. In the authenticator/user provider, reject a token whose `token_version` differs from the user's current value (`401`). Tokens issued before this change lack the claim: treat a missing claim as version 1 so existing sessions keep working until the first password change.
3. `PUT /token-renew` must also refuse a token with an outdated version.

Since the endpoint returns `204`, the frontend clears its session and redirects to login.

## 6. Tests

Unit (`tests/Unit/User/...`):

- `ChangePasswordHandlerTest` (update): wrong current password → `InvalidUserPassword` and `registerFailure`; locked → `ChangePasswordRateLimited` without touching the service; same password → `PasswordUnchanged`; 7- and 45-char new password → `InvalidNewPassword`; unknown user → `NotFoundUser`; success saves a new hash, increases `tokenVersion`, resets the limiter.
- `ChangePasswordServiceTest` (new): empty current password is **rejected**, not bypassed.
- `PasswordTest`: boundaries 7/8/44/45.
- `UserPasswordChangedEventHandlerTest`: sends the email; a notifier failure does not propagate.
- Limiter test: 3 failures then locked; success resets.

Acceptance (`tests/Acceptance/User/Controller/ChangeCurrentUserPasswordControllerTest.php`):

- `204`; the user can log in with the new password and not the old one.
- `422 user.invalid_password` with a wrong current password; password unchanged.
- Fourth attempt after 3 wrong ones → `429` with `Retry-After`.
- `400` for missing fields, wrong types, new password outside 8–44.
- `401` without token.
- `PATCH /users/{id}/change-password` now returns `404`/`405`.
- Token issued before the change gets `401` afterwards; renew with it is refused.

Run with `make tests/unit/run`, `make tests/acceptance/run`, `make linters/run`.

## 7. Delivery plan

Each step is independently mergeable and leaves the suite green.

1. **Remove the old endpoint** — delete `ChangeUserPasswordController`, its route, OpenAPI path and `ChangeUserPasswordRequest`. Delete or adapt tests that referenced it. *Breaking for any client using it; the frontend does not (it only calls `/me`), confirm with a grep of `librenotes-frontend`.*
2. **Domain hardening** — `Password` VO to 8–44 with `InvalidNewPassword`; `PasswordUnchanged`; make `currentPassword` required in `ChangePasswordService`; `ErrorUserChangePassword` → `CriticalException`; `ChangePasswordCommand` rename; handler stops swallowing domain errors. Update/extend unit tests. Check where else the `Password` VO is used (`CreateUser`, `SignUp`): the 8-char minimum now applies there too — update their schemas/tests and OpenAPI accordingly.
3. **New endpoint** — `ChangeCurrentUserPasswordController`, route, OpenAPI `/me/password`, schema, acceptance tests (without rate limit and token checks yet). `make linters/run` (regenerate baseline only if unavoidable).
4. **Token versioning** — `User::changePassword` bumps `tokenVersion`; claim in login/renew; check in the authenticator; tests for revoked tokens and legacy tokens without the claim.
5. **Rate limiting** — config, `ChangePasswordAttemptLimiter` interface + Symfony implementation, `ChangePasswordRateLimited`, 429 mapping and `Retry-After`; unit and acceptance tests.
6. **Email notification** — `UserPasswordChanged` event, handler, notification template (+ seed/migration if templates are persisted — check `Notification` module; if so, generate via `make doctrine/migrations/diff`), tests.
7. **Wrap-up** — full run of unit + acceptance + linters; update `etc/openapi`; note the change in the frontend spec (`/me/password`, 422/429 handling, forced re-login); set this spec's status to *implemented* and add a "deviations" section if anything changed.

No table or column changes are expected (steps 1–5); step 6 may add a migration only if notification templates live in the database.

## 8. Decisions

| # | Question | Decision |
| - | -------- | -------- |
| 1 | Password length | 8–44 characters |
| 2 | Wrong current password status | 422 |
| 3 | Response after success | `204`, client logs in again |
| 4 | Email notice | Yes, via `UserPasswordChanged` |
| 5 | Rate limiting | Yes, 3 failed attempts (15-minute fixed window, per user) |
| 6 | Admin password reset | Not provided; `/users/{id}/change-password` is removed |

## 9. Implementation status and deviations

Everything in §2–§7 is implemented. Differences from the design above:

1. **Initial vs. later password.** `User::changePassword()` is unchanged (still used by signup and admin creation, which must neither bump the token version nor send a "changed" email). The new `User::updatePassword(HashedPassword, now)` calls it, increases `tokenVersion` and records `UserPasswordChanged`; `ChangePasswordService` uses that one.
2. **Refresh tokens are revoked explicitly.** Refresh tokens are opaque DB rows (not JWTs), so a version claim cannot invalidate them. `ChangePasswordHandler` revokes every active refresh token of the user via `RefreshTokenRepository`; `PUT /token-renew` also loads the user to put the current `token_version` into the new access token (and refuses a refresh token whose user no longer exists).
3. **Version claim.** `POST /login` and `PUT /token-renew` add `token_version`; `JwtAuthenticator` rejects (`401`) a token whose claim differs from the user's version, treating a missing claim as `1`.
4. **`ErrorUserChangePassword`** now extends `CriticalException` but the handler no longer wraps anything in it, so it is only registered (500) in the controller map for safety.
5. **Email flag.** The notice reuses `USER_SEND_ACTIVATION_EMAIL` (`user.send_activation_email`) as the single "this instance sends user emails" switch instead of introducing a second env var. The template key is **`user.password_changed`**; notification templates live in the database (no seed), so an admin must create that template (channel `email`, per locale) through the Notification templates API for the email to be deliverable. A missing template or any delivery problem is logged and never fails the change.
6. **`Retry-After`.** `ApiExceptionSubscriber` adds the header for any `429` whose `params` carry an integer `retry_after`, and `ApiResponseSubscriber` now keeps the original response headers when it rebuilds JSON responses (it used to drop them).
7. **Rate limiter.** `consume(0)` of Symfony's sliding window is always "accepted", so the lock is detected via `getRemainingTokens() < 1`.
8. **`additionalProperties: false`** is enforced on the body, and `getParams()` merges query-string values into it, so `PATCH /me/password?x=1` returns `400`.
9. **Baseline.** The `ChangeUserPasswordController` and `ErrorUserChangePassword::params()` entries were removed from `phpstan-baseline.neon` by hand.
10. **No migration** was needed (steps 1–6 change no table or column).

Known pre-existing issues, untouched: 10 acceptance tests fail before and after this work (Configuration x6, SignUp x3, ActivateAccount x1 — the test environment has `USER_REGISTRATION_ENABLED=false` and no seeded configuration), and `make linters/run` reports one error in `UserRegistrationDisabled::params()`.
