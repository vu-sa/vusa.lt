---
title: Developers
---

# Developers

This chapter is for someone starting to maintain or build the vusa.lt code. It explains how a
request travels from the browser through Laravel and back to a Vue page. The rest of the guide
(in Lithuanian) covers how to use the platform.

## The stack

vusa.lt is one **Laravel** application that serves both the public website and the internal
platform **Mano VU SA** (`/mano`). Pages are written in **Vue 3**, with **Inertia.js** between them
and Laravel. A controller returns neither HTML nor an API payload, but a page name with its data
(props). Inertia renders the matching Vue component in the browser. There is no separate API to
write for pages, and routing, permissions and validation stay in Laravel.

If you have seen MVC, most of it will look familiar: route → controller → model → view, where the
view is a Vue page served through Inertia. The app is big because of how many features it has, not
because of an unusual architecture. Only a few places need extra attention:

- **Inertia** – the controller returns a page name and data, not a Blade view or an API response.
- **Permissions** – `ModelAuthorizer` with scopes (`*`, `padalinys`, `own`) resolved through duties
  and roles. This goes beyond plain Laravel policies.
- **Tenant from the subdomain**, and the final `{permalink}` route on the public site.
- **Work outside the controller** – model hooks and the queue, while Typesense search is queried
  directly from the browser.

Data lives in **MySQL**, cache, sessions and queues run on **Redis**, and search on **Typesense**.
Locally everything runs through **Laravel Sail** (Docker).

## Where things live

| Folder | What it holds |
| --- | --- |
| `bootstrap/app.php` | Middleware groups, route file registration, exception handling |
| `routes/admin.php`, `routes/web.php`, `routes/api.php` | `/mano/*`, public and `/api/v1/*` routes |
| `app/Http/Controllers` | Controllers: `Admin/`, `Public/`, `Api/` |
| `app/Http/Requests` | FormRequest classes – permissions (`authorize()`) and validation (`rules()`) |
| `app/Policies`, `app/Services/ModelAuthorizer.php` | Who may do what |
| `app/Models` | Eloquent models |
| `app/Services`, `app/Actions` | Logic shared by several controllers |
| `resources/js/Pages` | Vue pages returned by `Inertia::render()` |
| `resources/js/Components` | Components: `ui/` → `Patterns/` → domain folders → `Layouts/` |

## Reading the diagrams

The diagrams are interactive: hover over or tap a step to see its explanation and a link to the
file on GitHub. Colour shows the layer, a dashed arrow is background work and a red
one is an error path. Under each diagram the same steps are listed as text.

- [Request lifecycle](./request-lifecycle) – the overall picture and what happens after a save.
- [Example: a calendar event](./example-calendar-event) – one event from creation in Mano VU SA to its public page.

## Next

The code rules followed by both people and AI agents are in the repository's
[`AGENTS.md`](https://github.com/vu-sa/vusa.lt/blob/main/AGENTS.md) and the `.ai/rules/` folder.
More detailed guides: [controllers](https://github.com/vu-sa/vusa.lt/blob/main/app/Http/Controllers/CLAUDE.md),
[components](https://github.com/vu-sa/vusa.lt/blob/main/resources/js/Components/CLAUDE.md),
[tests](https://github.com/vu-sa/vusa.lt/blob/main/tests/CLAUDE.md).
