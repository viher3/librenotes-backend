# LibreNotes — Bulk Actions (Backend Spec)

> **Status: implemented** (attachment move, `POST /bulk/trash`, `POST /bulk/move`; tests in `tests/Acceptance/Notes/Controller/BulkActionsTest.php`, `NoteAttachmentsTest.php`, `tests/Unit/Notes/Domain/Service/ItemSelectionTest.php`). Frontend counterpart: `librenotes-frontend/etc/specs/bulk-actions.md`. Builds on [notes-data-model.md](./notes-data-model.md) (§1 endpoints, §10 notes as containers). Follow `etc/docs/*` (DDD layout, VOs for primitives, one `exceptions()` map per controller, snake_case JSON, OpenAPI under `etc/openapi/notes/`).

## 1. Goal

Let the client act on many tree items in a single request:

1. **Bulk move** — move folders, notes, links and attachments to another folder, to a note, or (except attachments) to the top level.
2. **Bulk delete** — send folders, notes, links and attachments to the trash.

Attachments also travel with their container: moving or trashing a folder or a note moves or trashes the files it holds (§2.6).

Today every operation is per item (`PATCH /{folders|notes|links}/{id}/move`, `DELETE /{folders|notes|links}/{id}`). Looping over them from the client costs N round-trips, leaves the tree half-changed when one call fails, and fires N cache invalidations. A single transactional request fixes all three.

**Out of scope**: permanent deletion and restore from the trash (see open question 2), copy/duplicate, bulk edit of tags/icons.

## 2. Semantics

### 2.1 Atomicity — all or nothing

The whole request runs in one DB transaction (`TransactionalCommandHandler`). Every item and the destination are validated **before** any change; if anything is invalid the response is an error and **nothing** is modified. There are no partial successes, so the client never has to reconcile a half-applied selection.

### 2.2 Item identity and ownership

- An item is `{ "type": "folder" | "note" | "link" | "attachment", "id": "<uuid>" }`.
- The same `(type, id)` repeated is collapsed silently.
- An item that does not exist, belongs to another user, or is already in the trash fails the request with that type's `*.not_found` (404, including `attachment.not_found`). Ownership is never distinguishable from absence (same as the single endpoints).
- The not-in-trash check on the items themselves is explicit in the bulk handlers: the existing single move handlers (`MoveNoteHandler`, `MoveFolderHandler`) only check `belongsTo`, so their validation cannot be reused as-is.
- Limits: **1 ≤ items ≤ 100** (`400 validation_error` otherwise).

### 2.3 Normalisation of nested selections

If the selection contains an item **and** one of its ancestors (a note whose parent note is also selected; a note/link/attachment/folder inside a selected folder; an attachment bound to a selected note), the descendant is dropped: it travels with its ancestor, which is what file managers do and what makes the result independent of the order of `items`. Ancestry follows both hierarchies (folder → subfolders/notes/links/attachments, note → child notes/links/attachments). `count` in the response reports the items **after** normalisation.

### 2.4 Bulk move

Destination — same shape as the single move endpoints (`ContentPlacement`, §10 of the data model): optional `folder_id` **or** `parent_note_id` (mutually exclusive); neither means the top level.

Rules, all checked up front:

| Rule                                                                                                                                          | Error                                                                             |
| --------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------- |
| Destination folder / parent note must exist, be the user's and not be in the trash                                                            | `folder.not_found` / `note.parent_not_found` (same status as today's single move) |
| The selection contains a folder and the destination is a note (folders only live in folders or at the top)                                    | `notes.invalid_location` (422)                                                    |
| Destination is, or is inside, a selected folder (`folder.cycle_detected`) or a selected note / one of its descendants (`note.cycle_detected`) | same codes as the single move                                                     |
| The selection contains an attachment and the destination is the top level (files need a folder or a note)                                     | `attachment.invalid_location` (422)                                               |
| Item already at the destination                                                                                                               | not an error: no-op for that item, still counted                                  |

Attachments follow the placement rule of the single attachment move, which **does not exist in the backend yet** and is added by this work (§3.4): folder or note, never the top level. An attachment whose note is in the trash is rejected as not found by the not-in-trash check.

Moving updates `updated_at` of each moved item like the single endpoint does. Ordering inside the destination is not defined (there is none today).

### 2.5 Bulk delete (to trash)

Same behaviour as the single `DELETE`, applied to each (normalised) item: a folder takes its whole subtree (subfolders, notes, child notes, links, attachments); a note takes its descendants and attachments (`NoteTreeLifecycle`, `NoteAttachmentLifecycle`). Everything becomes restorable through the existing `POST /trash/.../restore`. Files on disk are untouched until permanent deletion.

An `attachment` item is trashed on its own, like `DELETE /attachments/{id}`: it is listed separately in `GET /trash` and restored with `POST /trash/attachments/{id}/restore` (not while its note is in the trash).

### 2.6 Attachments travel with their container

Moving or trashing a folder or a note affects the files it holds **without listing them**:

- **Move.** Attachments reference their container (`folder_id` for files in a folder, `note_id` with `folder_id = NULL` for files of a note; data model §9), so they follow it. Moving a folder or note must not require touching its attachments; an acceptance test must prove they stay reachable (`GET /attachments/{id}`, folder/note contents) at the new location.
- **Trash.** Trashing a folder or note trashes its attachments (`NoteAttachmentLifecycle`, `NoteTreeLifecycle`); restoring brings them back. Descendant attachments are not listed separately in `GET /trash`.
- Selecting a container **and** its attachments is normalised (§2.3): the files are dropped and only the container is processed.

## 3. API

Conventions: JSON, snake_case, JWT required, owner taken from the token (`BaseApiController::currentUserId()`), input validated with an inline JSON Schema.

### 3.1 `POST /bulk/move`

```json
{
  "items": [
    { "type": "folder", "id": "…" },
    { "type": "note", "id": "…" },
    { "type": "link", "id": "…" },
    { "type": "attachment", "id": "…" }
  ],
  "folder_id": "…", // optional
  "parent_note_id": null // optional; send neither for the top level
}
```

`200` → `{ "data": { "count": 3 } }`.

### 3.2 `POST /bulk/trash`

```json
{
  "items": [
    { "type": "note", "id": "…" },
    { "type": "folder", "id": "…" }
  ]
}
```

`200` → `{ "data": { "count": 2 } }`.

(`POST` rather than `DELETE` with a body: many proxies/clients drop DELETE bodies. The path says _trash_ because that is what it does; permanent deletion stays in `/trash`.)

### 3.3 Errors

No new domain codes. HTTP statuses follow the role of the entity, as in the single endpoints: a missing/foreign/trashed **item** → `404`; a missing/foreign/trashed **destination** (`folder.not_found`, `note.parent_not_found`) → `422`; cycles and invalid locations → `422`. The controller's `exceptions()` map must distinguish the two uses of `FolderNotFound` (item vs destination), implemented with `DestinationFolderNotFound extends FolderNotFound` (same `folder.not_found` code, 422; the exact class wins over its parent in `ApiExceptionsHttpStatusCodeHandler`). Reused: `folder.not_found`, `note.not_found`, `link.not_found`, `attachment.not_found`, `attachment.invalid_location`, `folder.cycle_detected`, `note.cycle_detected`, `folder.parent_not_found`/`note.parent_not_found`, `notes.invalid_location`, `validation_error` (400). Because the request is atomic, the error body identifies the first offending item only through the code; the client re-fetches to see current state.

### 3.4 `PATCH /attachments/{id}/move` (prerequisite, new)

The frontend client and its generated schema already call this endpoint, but the backend has no route, use case or OpenAPI entry for it. It is implemented as part of this work, and the bulk move reuses its domain behaviour.

```json
{ "folder_id": "…", "note_id": null } // exactly one of the two
```

`200 {}`. Rules: exactly one of `folder_id` / `note_id` (neither or both → `attachment.invalid_location`, 422); target folder / note must exist, be the user's and not be in the trash (`folder.not_found` / `note.not_found`, 422); the attachment must exist, be the user's and not be in the trash (`attachment.not_found`, 404). Moving to a folder sets `folder_id` and clears `note_id`; moving to a note sets `note_id` and clears `folder_id` (data model §9). Add `Attachment::moveTo(?FolderId, ?NoteId)`, use case `MoveAttachment/` (transactional), `MoveAttachmentController`, route, and OpenAPI. Note the property name `note_id` (the notes/links move endpoints use `parent_note_id`); the bulk endpoint keeps `parent_note_id` and maps it.

## 4. Design

New use cases in `src/Notes/Application` (one folder per use case, per `etc/docs/FolderStructure.md`):

- `BulkMoveItems/` — `BulkMoveItemsCommand` (`ownerId`, `items`, `folderId`, `parentNoteId`) + `BulkMoveItemsHandler`.
- `BulkMoveItemsToTrash/` — `BulkMoveItemsToTrashCommand` (`ownerId`, `items`) + handler.

A third piece, `MoveAttachment/`, comes first (§3.4). The two bulk handlers implement `TransactionalCommandHandler` and:

1. Load every item (folder, note, link, attachment) through its repository, checking `belongsTo` and `isDeleted` (→ `*NotFound`).
2. Normalise with a new domain service `ItemSelection` (built on `NoteHierarchy` and a folder-ancestor walk; same 100-level guard) → returns the top-most items.
3. For move: resolve the destination **once** with `ContentPlacement`, run the rules of §2.4 against the whole set (cycle checks must consider **every** selected note/folder against the destination at once; `NoteHierarchy::assertCanBePlacedUnder` validates one note, so extend it or add a set-level check in `ItemSelection`), then call the existing aggregate behaviour (`moveTo`) per item. For trash: reuse the existing per-item logic (`MoveFolderToTrash`/`MoveNoteToTrash`/`MoveLinkToTrash` handlers' bodies) — extract their recursive part into domain services (`FolderTreeLifecycle` next to `NoteTreeLifecycle`) rather than invoking handlers from handlers, so both single and bulk paths share it.
4. Return the count.

Controllers `BulkMoveController` / `BulkTrashController` under `src/Notes/Infrastructure/Ui/Controllers`, routes in the module's `routing.yaml`, `dispatchTransactional`.

Performance: avoid N+1 where cheap — fetch items by id lists (`findByIds(array $ids, OwnerId $ownerId)` on each repository) and the ancestor chain once per distinct parent. The 100-item cap keeps the worst case bounded.

## 5. Documentation

Add both paths and the `BulkItem` (type enum includes `attachment`) / `BulkMoveRequest` / `BulkTrashRequest` / `BulkResult` schemas to `etc/openapi/notes/{paths,schemas}.yaml`. The frontend regenerates its client from `/api/doc.json` (`npm run api:fetch && npm run api:generate`), so this must ship before the frontend work.

## 6. Tests

Unit (per handler / `ItemSelection` / `MoveAttachmentHandler`): normalisation (note + ancestor note, item + ancestor folder, duplicates), cycle detection against a selected descendant, folder → note rejected, attachment → top level rejected (`attachment.invalid_location`), destination in trash, not-found/foreign/trashed item aborts with nothing saved, limits.

Acceptance (`tests/Acceptance/Notes/Controller/BulkActionsTest.php`, plus single attachment move in `NoteAttachmentsTest.php`: folder → note, note → folder, neither/both target → 422, foreign user → 404): mixed move to folder / note / top level; moving a folder or note with attachments keeps the files reachable at the new place; standalone attachments moved to a folder and under a note; move is atomic (one bad id → everything unchanged); user B cannot move or trash user A's items (404, A's data intact); bulk trash cascades (subtree, attachments, links), standalone attachments are trashed and restorable, and each item is restorable via `/trash`; trashed children of a trashed parent are not listed twice; 101 items → 400; empty `items` → 400.

## 7. Delivery order

0. `MoveAttachment` use case, endpoint, OpenAPI and tests (§3.4).
1. `ItemSelection` + repository `findByIds` + `FolderTreeLifecycle` extraction (refactor of the single trash handlers, tests unchanged).
2. Bulk trash use case + endpoint + OpenAPI.
3. Bulk move use case + endpoint + OpenAPI.
4. Acceptance tests; publish `openapi` for the frontend.

## 8. Open questions

1. **Partial success instead of atomic?** Proposed atomic for simplicity and predictability. If users routinely select stale items, switch to a `{ "moved": [...], "failed": [{ "type", "id", "code" }] }` response.
2. **Bulk permanent delete / restore from the trash?** Natural follow-up (`POST /bulk/trash/restore`, `POST /bulk/trash/purge`); not included here.
3. **Cap of 100 items** — enough for a screen of cards; raise if "select all" across large folders is wanted.
