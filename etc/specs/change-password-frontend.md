# LibreNotes — Change Own Password (Frontend Spec)

> **Status: implemented** (see §9 for the deviations; §9 is authoritative). Frontend counterpart of [change-password.md](./change-password.md) (backend, implemented). Target repository: `../librenotes-frontend` (React 19 + Vite + TypeScript, see its `README.md` and `etc/specs/mvp.md`). Follow its conventions: strict types, domain types in camelCase, all server access through the repository interfaces, no literal strings in JSX (ESLint `i18next` rule), es/en locales.

## 1. Goal

A signed-in user can change their account password from the account menu. After a successful change every session ends (backend revokes all access and refresh tokens), so the app signs the user out and sends them to the login page with a confirmation message.

## 2. Backend contract (already implemented)

`PATCH /me/password` — Bearer.

```json
{ "current_password": "string (1-44)", "new_password": "string (8-44)" }
```

| HTTP | `code` | Meaning | UI reaction |
| ---- | ------ | ------- | ----------- |
| 204 | — | Changed; **all tokens are now invalid** | Clear session, go to `/login` with a success notice |
| 400 | — (`{ errors: [...] }` → `ApiError` `validation_error`) | Schema violation | Generic form alert (client validation should prevent it) |
| 401 | `security.*` | Missing/invalid token | Handled globally by the HTTP client (renew, then session expiry) |
| 404 | `user.not_found` | User gone | Generic alert |
| 422 | `user.invalid_password` | Wrong current password | Error on the *current password* field |
| 422 | `user.password_unchanged` | New equals current | Error on the *new password* field |
| 422 | `user.invalid_new_password` | Breaks length rules (`params.min`, `params.max`) | Error on the *new password* field |
| 429 | `user.change_password_rate_limited` | 3 failed attempts; `params.retry_after` seconds, also `Retry-After` header | Disable the form, show a countdown, re-enable when it ends |
| 5xx / network | `network_error`, … | — | Generic alert, form stays filled |

Notes that matter for the client:

- A wrong current password is `422`, **not** `401`, so it never triggers the client's renew-and-retry logic.
- The request that succeeds is the last one the current tokens can make. Do not call `/token-renew` or `DELETE /token-renew` afterwards: the refresh token is already revoked.
- The account password has nothing to do with the per-document encryption password (`etc/specs/encryption.md` in the frontend): changing the account password does not touch encrypted documents, and the dialog must not suggest otherwise.

## 3. Backend prerequisites (small, in this repository)

1. **Signup/create-user length cap.** `POST /signup` and `POST /users` only declare `minLength: 8`. Since the `Password` VO now caps at 44 characters and `InvalidNewPassword` is not mapped in those controllers, a 45+ character password yields a `500`. Add `"maxLength": 44` to both JSON Schemas and the OpenAPI schemas (`SignUpRequest`, create-user request), so the frontend can rely on `400`.
2. **Email template.** An admin must create the `user.password_changed` template (channel `email`, per locale) through the Notification templates API for the notice email to be deliverable (the change works without it).
3. **Republish OpenAPI** is automatic; the frontend regenerates its types from `/api/doc.json` (§5.1).

## 4. UX

### 4.1 Entry point

`UserMenu` (account modal) gets a **"Change password"** button next to *Sign out*. It closes the account modal and opens `ChangePasswordDialog` (a `Modal`, same component family as `PromptDialog`/`ConfirmDialog`). No new route.

### 4.2 Dialog

Fields (React Hook Form + Zod, the new `PasswordField` of §4.4 — a `TextField` with a show/hide toggle — and `autoComplete` values for password managers):

| Field | `autoComplete` | Rules (i18n keys in `common:validation`) |
| ----- | -------------- | ---------------------------------------- |
| Current password | `current-password` | required (`validation.required`) |
| New password | `new-password` | 8–44 chars (`validation.passwordMin`, new `validation.passwordMax`); must differ from current (`validation.passwordUnchanged`) |
| Repeat new password | `new-password` | equals new (`validation.passwordMismatch`) |

- Submit button disabled while submitting; label switches to "Changing…".
- Hint under *New password*: "Between 8 and 44 characters." Limits come from `MIN_PASSWORD_LENGTH` / new `MAX_PASSWORD_LENGTH` in `src/data/rules.ts`.
- Each of the three fields has its own show/hide toggle; *New password* also shows the strength meter (§4.4).
- Errors: server field errors are placed with `setError` (`user.invalid_password` → current, `user.password_unchanged` / `user.invalid_new_password` → new); anything else goes to an `Alert` at the top of the dialog via `errorMessage(error)`.
- On `429`: the form is disabled and an `Alert` shows "Too many failed attempts. Try again in {{time}}." with a live countdown from `params.retry_after` (mm:ss); when it reaches 0 the form re-enables. Reopening the dialog before then simply fails again with a fresh `retry_after`.
- Fields are cleared when the dialog closes, and every field goes back to hidden; passwords are never stored (no `localStorage`, no logs).
- Accessibility: focus the first field on open, `aria-invalid`/`aria-describedby` through `TextField`, errors announced (`role="alert"`), Escape closes unless submitting.

### 4.3 After success

1. `AuthProvider` ends the session locally (clear tokens, `queryClient.clear()`), without calling the server's logout.
2. State becomes `anonymous` with a new reason `passwordChanged`.
3. The guards redirect to `/login`, where `LoginPage` shows a success `Alert`: "Your password was changed. Sign in with the new one." (new key `auth:login.passwordChanged`, alongside `sessionExpired` and `activated`).
4. The preferred language stays: `applyLocale` already persisted it; no extra work.

### 4.4 Show/hide toggle and strength meter

**Show/hide (`PasswordField`).** A button at the end of the input, inside the field's border, toggles `type` between `password` and `text`.

- It is a real `<button type="button">` with `aria-pressed`, an accessible name that changes with the state ("Show password" / "Hide password"), and an icon (eye / eye-off, inline SVG like the rest of the app). It is reachable by keyboard right after the input and never submits the form.
- Default is hidden. State is per field and local; it resets when the dialog closes or the page unmounts.
- The input keeps its `autoComplete` and `aria-describedby` wiring from `TextField`; the toggle must not change the field's value or caret position (focus stays on the toggle only if the user activated it with the keyboard).
- Reused wherever the user *chooses* a password: the three dialog fields and the register form (*Password* and *Repeat the password*). Out of scope: the login field and the document-encryption dialogs (the component is drop-in for them later).

**Strength meter (`PasswordStrength`).** Shown under *New password* in the dialog and under *Password* in the register form, only once the user has typed something.

- Purely **advisory**: it never blocks submit. Validation stays the 8–44 rule.
- Four levels — *Weak*, *Fair*, *Good*, *Strong* — shown as a four-segment bar **and** as text, so the information never depends on colour alone (colours: red, amber, lime, green with sufficient contrast in light and dark themes; unfilled segments in neutral).
- Accessibility: the bar is `role="meter"` with `aria-valuemin="0" aria-valuemax="4" aria-valuenow`, `aria-valuetext` equal to the label, and the label sits in a `aria-live="polite"` region that updates debounced (≈300 ms) so screen readers are not flooded while typing.
- Computed locally in `src/lib/passwordStrength.ts` — a pure function `scorePassword(password, context?)` returning `{ level: 0|1|2|3, reasons: StrengthHint[] }` plus a bar mapping. **No third-party library** (zxcvbn is ~800 KB; the app avoids heavy deps) and **no network call**: the password never leaves the browser except in the final request.
- Heuristic (documented in the function, unit-tested):
  1. Base score from length: < 8 → level 0 ("Weak"), 8–11 → 1, 12–15 → 2, ≥ 16 → 3 (passphrases are rewarded over symbol soup).
  2. Variety: lower/upper/digit/symbol classes; fewer than 2 classes caps the level at 1, 3+ classes can add one level up to the length-based maximum +1.
  3. Penalties (each drops one level, minimum 0): a single repeated character or a short repeated block (`aaaaaaaa`, `abcabcabc`), a sequential run (`12345678`, `abcdefgh`, keyboard rows like `qwertyui`), a match against a small bundled list of the most common passwords (~100 entries, lower-cased, leet-folded), and containing the user's email local part or username (`context`).
  4. Always "Weak" for any password the form would reject (shorter than 8).
- Optional one-line **tip** under the bar for the single most useful improvement (`tooShort`, `addVariety`, `avoidRepeats`, `avoidCommon`, `avoidPersonal`), e.g. "Try a longer phrase of several words."; localized.
- Messages: "Weak / Fair / Good / Strong", prefixed "Password strength:". The *Current password* and *Repeat* fields never show a meter.

## 5. Implementation design

### 5.1 API types

Run `npm run api:fetch && npm run api:generate` (backend running) to regenerate `src/data/api/schema.d.ts` with `/me/password`. Commit `openapi.json` and the schema.

### 5.2 Data layer (`src/data`)

- `types.ts`: `export interface ChangePasswordInput { currentPassword: string; newPassword: string }`.
- `repositories.ts` — `AuthRepository`:
  ```ts
  /**
   * Changes the signed-in user's password. On success the backend has revoked every token, so the
   * local session is cleared too. Rejects with `user.invalid_password`, `user.password_unchanged`,
   * `user.invalid_new_password` or `user.change_password_rate_limited` (`params.retry_after`, seconds).
   */
  changePassword(input: ChangePasswordInput): Promise<void>
  ```
- `http/repositories.ts`: map to snake_case, `await unwrap(api.PATCH('/me/password', { body: { current_password, new_password } }))`, then `session.clear()`. A `204` has no body; `unwrap` already tolerates it.
- `rules.ts`: `MAX_PASSWORD_LENGTH = 44`.
- `mock/index.ts`: same behaviour as the backend — verifies the current password against the mock account, `422` codes above, 8–44 length (`user.invalid_new_password` with `params {min,max}`), `429` after 3 consecutive failures per user for 15 minutes (`params.retry_after`), success resets the counter and clears the session. Use the same `wait()`/error helpers as `updateProfile`.
- `contract/repositoryContract.ts`: add a `changePassword` block so mock and HTTP adapter share the behaviour (success + re-login with new password + old one rejected; wrong current; unchanged; too short/long; lockout after 3 failures; session cleared after success).

### 5.3 Shared UI (`src/components`, `src/lib`)

- `components/PasswordField.tsx` (new): wraps `TextField` (extend it to accept an optional `trailing` slot rendered inside the border, so the toggle does not duplicate label/hint/error wiring). Props: the `TextField` props minus `type`; `ref`/`register` spread must keep working with React Hook Form.
- `components/PasswordStrength.tsx` (new): props `{ password: string; context?: { email?: string; username?: string } }`; renders nothing for an empty password.
- `lib/passwordStrength.ts` (new): the pure scoring function and the small common-passwords list (`lib/commonPasswords.ts`, lower-case, ≤ 3 KB).
- Icons: inline SVG, in the style of `UserMenu` (no icon library is added).

### 5.4 Auth feature (`src/features/auth`)

- `AuthProvider.tsx`: add `changePassword(input)` to the context. Mirror `logout()`: set `signingOut.current = true` so the session subscription does not report an expiry, `await auth.changePassword(input)`, then `queryClient.clear()` and `setState({ status: 'anonymous', reason: 'passwordChanged' })`; restore the flag in `finally`. If the call rejects, the state is untouched. Extend `AuthState['reason']` with `'passwordChanged'`.
- `ChangePasswordDialog.tsx` (new): the form of §4.2; receives `onClose`.
- `UserMenu.tsx`: new button and dialog state.
- `LoginPage.tsx`: render the `passwordChanged` notice from the `reason`.
- `errors.ts`: no change (codes resolve through `errors:` keys).

### 5.5 i18n (`src/locales/{en,es}`)

| File | Keys |
| ---- | ---- |
| `common.json` | `userMenu.changePassword`; `validation.passwordMax` (`Use at most {{count}} characters`), `validation.passwordUnchanged`; `password.show`, `password.hide`; `password.strength.label` (`Password strength`), `.weak`, `.fair`, `.good`, `.strong`; tips `password.strength.tips.tooShort`, `.addVariety`, `.avoidRepeats`, `.avoidCommon`, `.avoidPersonal` |
| `auth.json` | `changePassword.title`, `.description`, `.current`, `.new`, `.newHint`, `.confirm`, `.submit`, `.submitting`, `.cancel`, `.locked` (`Too many failed attempts. Try again in {{time}}.`); `login.passwordChanged` |
| `errors.json` | `user.invalid_password`, `user.password_unchanged`, `user.invalid_new_password`, `user.change_password_rate_limited` |

Both locales are required; `locales.test.ts` already checks that they have the same keys. Spanish copy proposals: "Mostrar contraseña" / "Ocultar contraseña", "Seguridad de la contraseña: Débil / Aceptable / Buena / Fuerte", "Cambiar contraseña", "Contraseña actual", "Nueva contraseña", "Repite la nueva contraseña", "Tu contraseña se ha cambiado. Inicia sesión con la nueva."

### 5.6 Registration form alignment

`RegisterPage` currently validates only the minimum. Add `.max(MAX_PASSWORD_LENGTH, 'validation.passwordMax')` so users cannot hit the backend cap (and the hint reads "Between 8 and 44 characters"). Update `auth:register.passwordHint` accordingly. Switch its two password inputs to `PasswordField` and show `PasswordStrength` (with the typed email/username as context) under *Password*.

### 5.7 Files touched / added

```
src/data/api/schema.d.ts            (regenerated)   openapi.json   (refreshed)
src/data/types.ts  repositories.ts  rules.ts
src/data/http/repositories.ts       src/data/mock/index.ts
src/data/contract/repositoryContract.ts
src/components/PasswordField.tsx (new)  PasswordStrength.tsx (new)  TextField.tsx (trailing slot)
src/lib/passwordStrength.ts (new)  commonPasswords.ts (new)
src/features/auth/AuthProvider.tsx  ChangePasswordDialog.tsx (new)
src/features/auth/UserMenu.tsx      LoginPage.tsx   RegisterPage.tsx
src/locales/{en,es}/common.json  auth.json  errors.json
README.md   (data layer / e2e notes)
```

## 6. Tests

Unit/contract (Vitest, no network):

- Contract suite (§5.2) runs against the mock; the HTTP adapter is covered in `http/repositories.test.ts` with the existing fake server: request body is snake_case, `204` resolves and clears the session, each `422`/`429` body becomes an `ApiError` with the right `code` and `params`.

Pure functions (`src/lib/passwordStrength.test.ts`): level boundaries at 7/8/11/12/15/16 characters; variety cap (`aaaaaaaaaaaaaaaaaaaa` stays low, long lowercase-only passphrase is not over-penalised); each penalty (repeat, sequence, keyboard row, common list including leet-folded `p4ssw0rd`, email/username inclusion); empty string; Unicode/emoji input does not throw; result is deterministic and never reads outside the arguments.

Component: `PasswordField` — hidden by default, toggle flips `type` and `aria-pressed`/name, value and focus preserved, does not submit the form, works with `register`. `PasswordStrength` — nothing when empty, meter attributes (`aria-valuenow`/`aria-valuetext`), label updates after the debounce (fake timers), no colour-only information (text always present).

Component (`src/features/auth/auth.test.tsx` or a new `changePassword.test.tsx`, using `renderApp` and the mock adapter):

- Opens from the account menu; labels, `autoComplete` and focus; the three fields start hidden and each can be revealed independently; closing and reopening hides them again.
- Typing in *New password* shows the strength meter (and it does not appear under *Current*/*Repeat*); a weak password still submits when it meets the 8–44 rule.
- Client validation: required, 7 and 45 characters, mismatch, same as current — and no request is sent.
- Success: lands on `/login` showing the notice; the old password no longer logs in, the new one does.
- Wrong current password: error under the *current* field, form stays filled, dialog open.
- Three wrong attempts: lockout alert with countdown (fake timers), form disabled, re-enabled at 0.
- Network error: generic alert, values kept.
- Register form rejects a 45-character password, offers the show/hide toggle and shows the strength meter.

End-to-end (`npm run test:e2e`, live backend): extend the existing smoke test — change the throwaway account's password, assert that the old access token gets `401`, that logging in with the old password fails and the new one works.

Quality gates: `npm run typecheck`, `npm run lint`, `npm run format:check`, `npm test`.

## 7. Delivery plan

Each step leaves the frontend green and can be merged on its own.

1. **Backend prerequisites** (this repo): add `maxLength: 44` to signup/create-user schemas and OpenAPI, with a test; create the `user.password_changed` template in the dev environment. *Until step 1 ships, a 45+ character registration is a 500.*
2. **API types** (frontend): `api:fetch` + `api:generate`; typecheck still passes; commit `openapi.json` and the schema.
3. **Data layer**: `ChangePasswordInput`, `AuthRepository.changePassword`, `MAX_PASSWORD_LENGTH`, HTTP adapter, mock adapter, contract tests (red → green on both).
4. **Shared UI**: `TextField` trailing slot, `PasswordField`, `lib/passwordStrength.ts` + common-passwords list, `PasswordStrength`; unit and component tests (they do not depend on the data layer, so this step can be done in parallel with step 3).
5. **Provider and i18n**: `AuthProvider.changePassword` and the `passwordChanged` reason; locale keys in en/es; `LoginPage` notice. Test the provider transition.
6. **Dialog**: `ChangePasswordDialog` using `PasswordField` and `PasswordStrength`, with validation, server-error mapping and lockout countdown; `UserMenu` entry; component tests.
7. **Register alignment**: max length and hint, `PasswordField` and `PasswordStrength` in `RegisterPage`, plus test.
8. **E2E and docs**: extend the live smoke test, update the frontend `README.md` (mention the flow and that the e2e now rotates the throwaway account's password), run the four quality gates, then mark this spec *implemented* with a deviations section.

## 8. Decisions

| # | Question | Decision |
| - | -------- | -------- |
| 1 | Where does the form live? | Modal opened from the account menu, no new route |
| 2 | After success | Sign out locally and show a notice on `/login` (backend returns `204` and invalidates every token) |
| 3 | Lockout handling | Disable the form with a countdown from `params.retry_after` |
| 4 | Password limits in the UI | 8–44, single source in `src/data/rules.ts`, also applied to registration |
| 5 | Relation with document encryption | None; the dialog says nothing about encrypted documents |
| 6 | Show/hide toggle | Yes: `PasswordField`, on the three dialog fields and the register password fields; login and document-encryption dialogs not changed |
| 7 | Strength meter | Yes: advisory only (never blocks), 4 levels with text + bar, local heuristic without dependencies or network, on *New password* and on register |
| 8 | Blocklist / breach check (e.g. HaveIBeenPwned) | Not included: it would send password-derived data to a third party; only a small bundled common-passwords list |

## 9. Implementation status and deviations

All steps of §7 are done in `../librenotes-frontend`, except that the live end-to-end test could not be run here (§9.8). Differences from the design:

1. **OpenAPI/types were patched, not regenerated.** The backend on `develop` publishes a different search schema (`NotesSearchResponse`, no `tag` filter) than the one the frontend was built against, so a full `api:fetch` + `api:generate` breaks unrelated types. Only the delta of this feature was applied to `openapi.json` and `schema.d.ts`: `/me/password` and `ChangeCurrentUserPasswordRequest` added, `/users/{id}/change-password` and `ChangeUserPasswordRequest` removed, `maxLength: 44` on the sign-up and create-user passwords. A full refresh has to wait until the two branches agree on search.
2. **Prerequisite 1 done in this repo**: `maxLength: 44` on `POST /signup` and `POST /users` (JSON Schema + OpenAPI) with acceptance tests (`400` for 45 characters). The `user.password_changed` email template (prerequisite 2) is still an admin action in each environment.
3. **Strength meter details.** The meter exposes `aria-valuemin=1`, `aria-valuemax=4`, `aria-valuenow=level+1` (a value of 0 would not match a visible segment), and `scorePassword` returns `{ level, hint }` (one hint, not a list). Two heuristic changes: one or two distinct characters are always *Weak*; a password with a single character class is capped at *Fair*, or *Good* from 20 characters (instead of always *Fair*). Repeated blocks are detected by periodicity (up to 6 characters, 90% matches), so `xyz-xyz-xyz` counts.
4. **Show/hide button** has an accessible name that changes ("Show password" / "Hide password") and no `aria-pressed` (the two together are announced twice). It ignores `mousedown` so a click does not move the caret out of the input.
5. **`TextField`** got a `trailing` slot (the input is now wrapped in a `relative` container). `errorMessage()` now passes the backend's `params` to the translation, so `user.invalid_new_password` can show the real limits.
6. **Mock adapter.** Tokens carry the user's token version (`mock-access-<id>~<n>`; versionless ones count as 1), which is how a password change revokes sessions there. Its lockout uses `Date.now()` and the same 3 attempts / 15 minutes.
7. **Dialog.** Closing and reopening gives a fresh, hidden form. A lockout lasts for the dialog's lifetime only; reopening it simply gets a new `429` with a fresh `retry_after`. `RequireAuth` does not remember the page the user came from after a password change (same as sign-out).
8. **E2E.** `src/data/e2e/realBackend.e2e.ts` has the new password-change test (wrong current -> `422`, change, old access and refresh tokens rejected, old password refused, new password works) but it was **not run**: the local backend reports `registration_enabled: false` (`.env.local.php`), which the smoke test needs. It now uses 4 of the 5 logins per 15 minutes.

Verification: frontend `typecheck`, `lint`, `format:check`, `build` clean; 600 unit/component tests pass (72 new). Backend: unit tests pass; acceptance failures are the same 10 that existed before (environment).
