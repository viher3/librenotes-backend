# LibreNotes — Notes Data Model & Entities (Backend Spec)

> **Status: implemented** (see §9 for what was built and where it deviates from the original proposal below). Sections 1–8 keep the original design rationale; where the implementation differs, §9 is authoritative.

Backend counterpart of the frontend MVP spec (`librenotes-frontend/etc/specs/mvp.md`). It describes (1) what the `Notes` module already implements, (2) what the frontend MVP needs on top of it, and (3) the entities, tables, endpoints and conventions to add. Follow `etc/docs/*` (DDD layout, VOs for every primitive, XML mappings, migrations via `make doctrine/migrations/diff`).

## 1. Current state (`src/Notes`, branch `main`)

All aggregates are owner-scoped (`owner_id` = authenticated user id), use soft delete (`deleted_at`) and UUID ids.

| Aggregate    | Table        | Fields                                                                                                                                  |
| ------------ | ------------ | --------------------------------------------------------------------------------------------------------------------------------------- |
| `Folder`     | `folder`     | `id`, `name` (≤255), `parent_folder_id` (nullable), `owner_id`, `created_at`, `updated_at`, `deleted_at`                                |
| `Note`       | `note`       | `id`, `title` (≤255), `content` (Markdown, text), `folder_id` (nullable), `owner_id`, `created_at`, `updated_at`, `deleted_at`          |
| `Attachment` | `attachment` | `id`, `folder_id` (**required**), `owner_id`, `file_name`, `mime_type`, `size_bytes`, `storage_key` (unique), `created_at`, `deleted_at` |

Implemented endpoints (all require a JWT):

| Area        | Endpoints                                                                                                                                  |
| ----------- | ------------------------------------------------------------------------------------------------------------------------------------------ |
| Folders     | `POST /folders`, `GET /folders/contents?folder_id=` (root when omitted), `PATCH /folders/{id}` (rename), `PATCH /folders/{id}/move`, `DELETE /folders/{id}` (to trash) |
| Notes       | `POST /notes`, `GET/PATCH/DELETE /notes/{id}`, `PATCH /notes/{id}/move`                                                                     |
| Attachments | `POST /folders/{folderId}/attachments` (multipart, field `file`), `GET /attachments/{id}` (metadata only), `DELETE /attachments/{id}`      |
| Trash       | `GET /trash`, `DELETE /trash` (empty), `POST /trash/{folders\|notes\|attachments}/{id}/restore`, `DELETE /trash/{folders\|notes\|attachments}/{id}` (permanent) |

Domain error codes: `folder.not_found`, `folder.cycle_detected`, `note.not_found`, `attachment.not_found`, `notes.parent_folder_in_trash`, `notes.not_in_trash`.

Infrastructure: `LocalFileStorage` behind `FileStoragePort`; PHP upload limit already 50 MB (`etc/docker/php-fpm/conf/app.ini`).

## 2. Gaps against the frontend MVP

| #   | Frontend need                                         | Today                                                                                                   | Priority |
| --- | ----------------------------------------------------- | ------------------------------------------------------------------------------------------------------- | -------- |
| 1   | OpenAPI docs for the Notes module                     | None (`etc/openapi/` has no `notes/`; the module is invisible in `/api/docs` and in the generated client) | **P0**   |
| 2   | Current user / profile / language                     | No `GET /me`; no `locale` on `User`; login response body is undocumented                                | **P0**   |
| 3   | Attach files **to a document**                        | Attachments belong to a **folder** only (and `folder_id` is required, so none at root)                  | **P0**   |
| 4   | Download/view an attachment                           | Only metadata (`GET /attachments/{id}`); no endpoint streams the file                                   | **P0**   |
| 5   | Search over notes and links                           | None                                                                                                    | **P0**   |
| 6   | Saved links                                           | No `Link` entity                                                                                        | **P0**   |
| 7   | Tags on notes and links, filter by tag                | None                                                                                                    | **P1**   |
| 8   | Pin notes                                             | None                                                                                                    | **P1**   |
| 9   | List notes beyond a folder (recent, pinned, by tag)   | Only `GET /folders/contents`, unpaginated                                                               | **P1**   |
| 10  | Response format consistent with `etc/docs/Api.md`     | Notes responses are camelCase (`createdAt`, `folderId`); requests use snake_case (`folder_id`); docs mandate snake_case | **P1**   |
| 11  | Attachment size/type validation with domain errors    | Only PHP's `upload_max_filesize`                                                                        | **P1**   |

Notes on the frontend side of #1: the frontend generates its API client from `/api/doc.json`, so every endpoint below must be documented there. Also, `ResponseOk`/`ResponseNotFound`/`Info` currently serialize empty schemas as `[]` instead of `{}`, which makes the document invalid; fix at the source.

## 3. Target model

### 3.1 Entity overview

```
User 1───* Folder 1───* Note 1───* Attachment
              │  (self: parent)  │
              │                  ├───* NoteTag (tag name)
              ├───* Link ────────┴── LinkTag (tag name)
              └───* Attachment (folder-level, note_id null)
```

All tables keep: `id` (uuid, 36), `owner_id`, `created_at`, `updated_at` (where mutable), `deleted_at` (soft delete, same semantics as today's trash).

### 3.2 `User` (module `User`) — additions

| Column   | Type / VO                | Notes                                                                                   |
| -------- | ------------------------ | --------------------------------------------------------------------------------------- |
| `locale` | `varchar(5)`, `UserLocale` VO | Default `en`; allowed values `es`, `en` (validated by the VO, extendable); updatable through `PATCH /users/{id}` and `PATCH /me` |

### 3.3 `Folder` — no schema change

Already matches the frontend model (`parent_folder_id` ↔ `parentId`).

### 3.4 `Note` — additions

| Column   | Type              | Notes                                                              |
| -------- | ----------------- | ------------------------------------------------------------------ |
| `pinned` | `bool_vo`, default `false` | Pinned notes are listed first in "Pinned". `pinned_at` is not needed for the MVP. |

Tags are modelled as a value-object collection on the aggregate (`TagName[]`, normalized: trimmed, lower-cased, 1–50 chars, max 20 per note) persisted in `note_tag`:

| Table      | Columns                              | Constraints / indexes                                  |
| ---------- | ------------------------------------ | ------------------------------------------------------ |
| `note_tag` | `note_id` (36), `name` (50)          | PK `(note_id, name)`; index `(name)`; FK `note_id` → `note.id` ON DELETE CASCADE |

Search support: `FULLTEXT` index on `note (title, content)` (MySQL 8 InnoDB, `ngram` parser is not required for es/en).

`VALID_PARTIAL_UPDATE_FIELDS` becomes `title`, `content`, `pinned`, plus `tags` (full replacement of the set).

### 3.5 `Link` — **new** aggregate (`src/Notes`, same module)

| Column      | Type / VO                       | Notes                                               |
| ----------- | ------------------------------- | --------------------------------------------------- |
| `id`        | `LinkId` (Uuid)                 |                                                     |
| `owner_id`  | `OwnerId`                       |                                                     |
| `folder_id` | `FolderId`, nullable            | Same folder tree as notes                           |
| `url`       | `LinkUrl` (≤2048)               | Validated: absolute `http`/`https` URL only (reject `javascript:`, `data:`, etc.) |
| `title`     | `LinkTitle` (≤255)              | Required; the user types it (no metadata fetching in the MVP) |
| `note`      | `LinkNote` (text), nullable     | Optional description                                |
| `created_at`, `updated_at`, `deleted_at` | `datetime_vo` | Trash behaves like notes |

Table `link_tag (link_id, name)` mirrors `note_tag`. Indexes: `(owner_id, folder_id)`, `(owner_id, deleted_at)`, `FULLTEXT (title, note, url)`.

Domain behaviour: `rename/update(title, url, note)`, `moveTo(folder)`, `moveToTrash`, `restore`, `belongsTo(owner)` — same shape as `Note`. New exception `LinkNotFound` (`link.not_found`), `InvalidLinkUrl` (`link.invalid_url`).

### 3.6 `Attachment` — changes

| Change                       | Detail                                                                                                                       |
| ---------------------------- | ---------------------------------------------------------------------------------------------------------------------------- |
| Add `note_id` (nullable)     | `NoteId`; when set, the file belongs to a document (the frontend case). FK `note_id` → `note.id` ON DELETE CASCADE.         |
| Make `folder_id` **nullable** | Files at the root are allowed. Invariant: if `note_id` is set, `folder_id` equals the note's folder (kept in sync when the note moves) or is simply null — see open question 1. |
| Trash coupling               | Trashing a note trashes its attachments; restoring a note restores them (already the pattern used for folders).             |
| Validation                   | `FileSizeBytes` max 50 MB (`attachment.too_large`, HTTP 422); reject empty files (`attachment.empty`). MIME allowlist is **not** enforced in the MVP; downloads are always sent as attachments with `X-Content-Type-Options: nosniff` (see 4.3). |

Migration note: existing rows keep `note_id = NULL` and their `folder_id`, so no data migration is needed.

### 3.7 Tags (read model)

No `Tag` aggregate. `GET /tags` returns the distinct tag names of the user with usage counts, computed with a query over `note_tag ∪ link_tag` joined to the owner's non-deleted rows.

## 4. API additions

Conventions (from `etc/docs/Api.md` and `Controllers.md`): JSON, **snake_case** properties, `meta`/`data` envelope for lists, input validated with an inline JSON Schema, one `exceptions()` map per controller, routes in the module's `routing.yaml`, every endpoint documented under `etc/openapi/notes/`.

### 4.1 User / profile

| Endpoint        | Description                                                                                       |
| --------------- | ------------------------------------------------------------------------------------------------- |
| `GET /me`       | Current user: `id`, `email`, `username`, `locale`, `role`, `created_at`. Same data as `GET /users/{id}` but without needing an admin or knowing the id. |
| `PATCH /me`     | Updates `username` and `locale`. Password changes keep using `PATCH /users/{id}/change-password`.  |
| `POST /login`   | Document the success body in OpenAPI. It must expose the JWT and the user id (e.g. `{ "data": { "token": "...", "user_id": "..." } }`) so the frontend can bootstrap the session. Same for `POST /signup` (`201` with `{ "id": "..." }`). |

### 4.2 Notes, links, folders

| Endpoint                                   | Description                                                                                                        |
| ------------------------------------------ | ------------------------------------------------------------------------------------------------------------------ |
| `GET /notes`                               | Paginated list. Filters: `folder_id` (use `root` for no folder), `tag`, `pinned`, `order_by=updated_at\|created_at\|title`, `order=asc\|desc`, `page`, `size` (default 20, max 100). Returns summaries (no `content`). |
| `POST /notes`, `PATCH /notes/{id}`         | Accept `tags` (string[]) and `pinned` (bool) in addition to today's fields. `GET /notes/{id}` returns `tags`, `pinned` and the note's `attachments` (metadata). |
| `POST /links`                              | `url`, `title`, `note?`, `folder_id?`, `tags?` → `201 { "id" }`.                                                   |
| `GET /links`                               | Same pagination/filters as `GET /notes` (`folder_id`, `tag`).                                                      |
| `GET/PATCH/DELETE /links/{id}`, `PATCH /links/{id}/move` | Detail, update, move to trash, move to folder.                                                       |
| `GET /folders/contents`                    | Also returns `links`; each note summary includes `pinned` and `tags`. Add the Link variants to the trash endpoints: `POST /trash/links/{id}/restore`, `DELETE /trash/links/{id}`, and `links` in `GET /trash`. |
| `GET /tags`                                | `[{ "name": "work", "count": 7 }]`, ordered by count desc then name.                                              |

### 4.3 Attachments

| Endpoint                                   | Description                                                                                                         |
| ------------------------------------------ | ------------------------------------------------------------------------------------------------------------------- |
| `POST /notes/{noteId}/attachments`         | Multipart (`file`) upload bound to a note → `201 { "id" }`. Keeps `POST /folders/{folderId}/attachments` for folder-level files. |
| `GET /attachments/{id}`                    | Metadata (as today) plus `note_id`.                                                                                  |
| `GET /attachments/{id}/content`            | **New.** Streams the file for the owner only (JWT in `Authorization`). Headers: `Content-Type` from stored MIME, `Content-Disposition: attachment; filename*=UTF-8''…` (inline only for images when `?inline=1`), `X-Content-Type-Options: nosniff`, `Content-Length`. Backed by `FileStoragePort::read(storageKey)` (new method; local filesystem stream). |

The frontend downloads through `fetch` + `Blob` with the Bearer header, so no signed URLs are needed in the MVP.

### 4.4 Search

`GET /search?q=&type=note,link&tag=&page=&size=`

- Scope: current user, non-deleted rows only.
- `q` is required, min 2 chars; matches `note.title/content` and `link.title/note/url` using `MATCH … AGAINST (… IN BOOLEAN MODE)` (fall back to `LIKE` for 1–2 char tokens).
- Response `data[]` items: `{ "type": "note|link", "id", "title", "snippet", "folder_id", "tags", "updated_at" }`; `snippet` is a ~160-char plain-text excerpt around the first match (no HTML; the frontend highlights it).
- Query lives in `Application/Search` (Query + Handler + Response) with a `SearchRepository` port and a Doctrine DBAL implementation, because it spans two aggregates.

## 5. Error codes to add

| Code                      | HTTP | When                                                |
| ------------------------- | ---- | --------------------------------------------------- |
| `link.not_found`          | 404  | Unknown or foreign link                             |
| `link.invalid_url`        | 422  | URL is not an absolute http(s) URL                  |
| `tag.invalid`             | 422  | Empty/too long tag, or more than 20 tags            |
| `attachment.too_large`    | 422  | File larger than 50 MB                              |
| `attachment.empty`        | 422  | Zero-byte upload                                    |
| `user.locale_unsupported` | 422  | Locale outside the supported list                   |
| `search.query_too_short`  | 422  | `q` shorter than 2 chars                            |

Reminder: a foreign owner's resource must respond as **not found** (never 403), as the existing handlers do with `belongsTo`.

## 6. Cross-cutting tasks

1. **OpenAPI**: create `etc/openapi/notes/{paths,schemas}.yaml` covering every Notes endpoint (existing and new) and register it in `etc/openapi/openapi.yaml`; fix the empty-schema serialization (`[]` → `{}`) and make `servers` match the real base URL (routes live at the host root, not `/api`).
2. **Response casing**: switch Notes responses to snake_case (`created_at`, `folder_id`, `file_name`, `mime_type`, `size_bytes`, `parent_folder_id`, `deleted_at`) as `etc/docs/Api.md` requires. Do it before the frontend integrates (a breaking change that is free today).
3. **Cleanup**: drop the `NIL_UUID` fallback in the controllers; the firewall already enforces `IS_AUTHENTICATED_FULLY`, so `getUser()` is never null.
4. **Signup hardening**: `POST /signup` with a missing `email` returns `500` (`Undefined array key "email"`); validate with a JSON Schema and return `400`.
5. **Migrations**: one migration per aggregate change (`user.locale`; `note.pinned` + `note_tag` + FULLTEXT; `link` + `link_tag`; `attachment.note_id` + nullable `folder_id`). Review the generated SQL; never use `doctrine/update/force-danger`.
6. **Tests**: unit tests per new handler/VO; acceptance tests for owner isolation (user B cannot read/search/download user A's data) and for the trash cascade including links and note attachments.
7. **CORS**: currently `Access-Control-Allow-Origin: *`; restrict to the frontend origin(s) via env var before production.

## 7. Suggested delivery order

1. OpenAPI for existing Notes endpoints + snake_case + signup fix (unblocks the frontend client with no model change).
2. `GET /me`, `PATCH /me`, `User.locale`, documented `/login` and `/signup` bodies.
3. Attachments: `note_id`, `POST /notes/{id}/attachments`, `GET /attachments/{id}/content`, size validation.
4. `Note.pinned` + tags + `GET /notes` + `GET /tags`.
5. `Link` aggregate and its trash integration.
6. `GET /search`.

## 8. Open questions

1. Should an attachment bound to a note also keep a `folder_id`? Proposal: **no** — `note_id` set implies `folder_id = NULL`, and the note's folder is the source of truth, avoiding sync on moves.
2. Trash retention: purge items after N days automatically (scheduled command) or only manually (`DELETE /trash`)? The MVP assumes manual.
3. Tag casing: lower-case normalization is proposed; confirm it is acceptable for non-Latin tags (use `mb_strtolower`).
4. Do we need a storage quota per user now, or only the 50 MB per-file limit?
5. Is the frontend allowed to rely on `GET /notes` summaries without `content` (proposed), or does it also need a short `excerpt` field in lists?

## 9. Implementation status and deviations

All gaps in §2 are closed: every item has code, migrations, OpenAPI documentation and acceptance tests (`tests/Acceptance/Notes`, `tests/Acceptance/User`, `tests/Acceptance/Shared`).

| §2 # | Delivered |
| ---- | --------- |
| 1 | `etc/openapi/notes/{paths,schemas}.yaml`; `etc/openapi/{authentication,user,shared}` corrected (real `/login`, `/token-renew` and error shapes); `OpenApiDocumentationTest` fails if any application route is undocumented or the document contains empty-schema defects |
| 2 | `User.locale` (`UserLocale` VO, `en`/`es`), `GET /me`, `PATCH /me` (only `username` and `locale`) |
| 3 | `attachment.note_id` (nullable), `attachment.folder_id` now nullable, exactly one of them is set (`attachment.invalid_location`) |
| 4 | `GET /attachments/{id}/content` (streamed, `Content-Disposition: attachment`, `nosniff`, restrictive CSP; `?inline=1` only for png/jpeg/gif/webp) |
| 5 | `GET /search` |
| 6 | `Link` aggregate with CRUD, move, trash, restore and permanent delete |
| 7 | Tags on notes and links, `GET /tags` |
| 8 | `Note.pinned` |
| 9 | `GET /notes`, `GET /links` (paginated, filtered) |
| 10 | Notes responses are snake_case |
| 11 | 50 MB limit and empty-file check as domain errors (`attachment.too_large`, `attachment.empty`), including uploads rejected by PHP's own limit |

### Deviations from the proposal

- **Tags are a JSON column, not `note_tag`/`link_tag` tables** (`tags JSON NULL` on `note` and `link`, `Tags` value object). XML mappings of value-object collections without entities are awkward in Doctrine, and MySQL 8 covers the queries: `JSON_CONTAINS` for filtering and `JSON_TABLE` for `GET /tags`. NULL means "no tags".
- **Search is substring (`LIKE`) search, not FULLTEXT.** FULLTEXT ignores words shorter than 3 characters and stopwords (searching "the plan" would return nothing), which is surprising for find-as-you-type. Matching is case- and accent-insensitive through the table collation; wildcard characters in the query are matched literally. Cost is bounded by the per-owner row set. Revisit (FULLTEXT with ngram, or an external index) if a single user can own very large volumes. No FULLTEXT migration exists.
- **Attachments bound to a note have `folder_id = NULL`** (open question 1 resolved as proposed). There is no root-level upload endpoint: files are uploaded to a folder or to a note.
- **Attachments follow their note's lifecycle**: trashing/restoring/permanently deleting a note (directly, through its folder, or by emptying the trash) does the same to its attachments, files on disk included (`NoteAttachmentLifecycle` domain service). Attachments of a trashed note are not listed separately in `GET /trash`; an attachment trashed on its own is, and cannot be restored while its note is in the trash (`notes.parent_note_in_trash`).
- **List/search query parameters keep the project's existing names**: `page`, `size`, `orderBy`, `orderDirection` (camelCase, as `BaseApiController::getSearchParams` defines), plus `folder_id`, `tag`, `pinned`, `type`, `q`. Response properties are snake_case. Pagination uses the `meta` / `data` envelope (`page`, `size`, `total`, `last_page`).
- **Error codes** as proposed, plus `attachment.invalid_location` and `notes.parent_note_in_trash`. An unsupported `locale` is rejected with `400` by the request schema (`enum`) on `PATCH /me` and `PATCH /users/{id}`; the domain error `user.locale_unsupported` (`422`) is the second line of defence.

### Defects found and fixed along the way

- **Request validation never enforced `required`.** The controllers' JSON schemas use `"type": "array"` (the decoded body is a PHP array), and with that type json-schema ignores `required`; an empty body to `POST /signup` reached the handler and ended in a `500`. `RequestParamsJsonValidator` now validates bodies as JSON objects whenever the schema declares `properties` (unit-tested).
- Enabling that exposed two schemas with a wrong `required`: `PATCH /users/{id}` demanded `password` and `PATCH /users/{id}/change-password` had a single string `"currentPassword, newPassword"`. Both fixed and covered by tests.
- **`PATCH /notes/{id}` could never work** (private properties updated from `AggregateRoot::partialUpdate`); `Note`'s updatable fields are now `protected`, like `User`'s. Unknown fields now answer `422` (`invalid_update_fields`) on notes, links and users instead of `500`.
- The test database was missing and unreachable for the `core` user; `etc/docker/mysql/scripts/01-test-database.sql` now creates it on a fresh volume (and was applied manually to the running container). Attachment storage uses a separate directory in the `test` environment (`notes.attachments_dir`).
- The `NIL_UUID` fallback was removed from the Notes controllers (`BaseApiController::currentUserId()`); the firewall already guarantees an authenticated user. Other modules still use it.

### Known issues left alone

- **Security — any authenticated user can read, modify and delete any other user.** `GET`, `PATCH` and `DELETE /users/{id}` (and `PATCH /users/{id}/change-password`, which at least asks for the current password) only require *a* valid token; `access_control` in `config/packages/security.yaml` restricts just `POST /users`. Verified with two accounts: user B received `200` on `GET`, `PATCH` and `DELETE /users/<A's id>`, and `PATCH` can change `email`, `active` and `emailValidated`. `/me` is the safe self-service path; these routes should be limited to `ROLE_ADMIN` or to the owner. Not changed here because it alters existing behaviour outside this spec.
- `Configuration` acceptance tests fail locally because `CONFIGURATION_ENCRYPTION_KEYS` is commented out in `.env`.
- CORS is still `Access-Control-Allow-Origin: *` (§6 task 7).
- Notes can still be read by id after they are in the trash (`GET /notes/{id}`); only listings, search and downloads exclude trashed items.

## 10. Notes as containers (the tree)

A **note can hold other notes and links**, like a page that holds sub-pages, in addition to folders. Folders stay as pure containers; both can be mixed freely (folder → note → note → link ...).

- **Model.** `note.parent_note_id` and `link.parent_note_id` (nullable, indexed with `owner_id`). A note or link lives in a folder **or** under a note **or** at the root, never in two places (`notes.invalid_location`). The migration is additive; existing data is untouched. Attachments are unchanged: they belong to a note or to a folder.
- **Root.** "Root" now means _no folder and no parent note_. `GET /folders/contents` (no `folder_id`), `GET /notes?folder_id=root` and `GET /links?folder_id=root` exclude nested items; otherwise every nested note would also appear at the top level.
- **API.**
  - `parent_note_id` (mutually exclusive with `folder_id`) on `POST /notes`, `POST /links`, `PATCH /notes/{id}/move` and `PATCH /links/{id}/move`. A move with neither sends the item to the root.
  - `GET /notes/{id}/children` → `{ notes, links }`: the direct children, notes ordered by title, each with `child_count`.
  - `GET /notes` and `GET /links` accept `parent_note_id`.
  - `child_count` (live children: notes + links) on note summaries in folder contents, note lists and children; `parent_note_id` on note and link lists and details.
  - `GET /notes/{id}` returns `path`, and `GET /folders/contents` returns `folder.path`: the ancestors from the root down to the direct parent as `{type: folder|note, id, title}`, for breadcrumbs.
- **Rules.** A note cannot be placed under itself or under any of its descendants (`note.cycle_detected`). The target parent must exist, be the user's and not be in the trash (`note.parent_not_found`, `422`, like a missing target folder). Trees are limited in practice to 100 levels by the code that walks them.
- **Trash.** Trashing, restoring and permanently deleting a note does the same to everything under it (notes, links and all their attachments, files included), directly, through its folder, or when emptying the trash. Descendants of a trashed note are not listed separately in `GET /trash`; they come back with it. A child trashed on its own is listed, and cannot be restored while its parent note is in the trash (`notes.parent_note_in_trash`, `409`).
- **Implementation.** `ContentPlacement` (validates "folder or parent note"), `NoteHierarchy` (cycles, ancestor paths) and `NoteTreeLifecycle` (subtree trash/restore/delete, built on `NoteAttachmentLifecycle`). Tests: `tests/Acceptance/Notes/Controller/NoteTreeTest.php`.

Not done (still optional): manual ordering of siblings (needs a `position` column) and files attached directly to the root.
