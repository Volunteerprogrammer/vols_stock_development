# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A PHP volunteer-management web app (originally for a food bank / neighbourhood house) with a stock/inventory
tracking module, running on XAMPP/MySQL. Built on an **in-house MVC framework** vendored at `vendor/fw`
(namespace `fw\`) — not Laravel/Symfony/etc. There is no Composer at the app level (no root `composer.json`);
autoloading and DI are hand-rolled by the framework.

## Running / testing — no build step

There is no package manager, build tool, or test runner. Everything is plain PHP served by Apache (XAMPP) or run
via CLI.

- App entry point: `index.php` (serves via XAMPP at whatever the vhost/alias is configured to, e.g. `localhost/stock/`).
- Daemon entry point: `daemon.php` — runs `app\daemon\RosterReview::do_review()` (roster shortfall/digest/maintenance
  and stock-alert emails), intended to run from cron.
- Tests are standalone PHP scripts with hand-rolled `check($label, $result, $expected)` assertions (no PHPUnit) —
  run directly with `php`:
  - `php tests/smoke_collections.php` — no DB required; verifies `ManagerCollection`/`FormCollection`/`BodyCollection`
    lazily resolve every registered class correctly via the framework's DI.
  - `php tests/test_stock_events.php` — **requires a live DB** (connects to `localhost`/`root`/`vols`); seeds two
    throwaway `stock_location` rows, drives real table classes through delivery/issue/adjustment/transfer/stocktake
    scenarios, and cleans up its own rows at the end. Good template to follow for new stock-logic tests.
  - `test.php` at repo root is a personal scratch file, not a real test.
- `simulate.php` / `simulate_auto.php` (browser-run, not CLI) seed realistic demo stock-event data for manual QA —
  open in a browser and step through (or let auto-run) a hardcoded sequence of ~18 events.
- DB schema/seed changes live as loose, manually-run scripts in `sql/*.sql` (no migrations table, no ordering
  scheme — filenames are descriptive, e.g. `stock_setup.sql`, `roster_setup.sql`). Most are gitignored except a
  few explicitly whitelisted in `.gitignore` (`regenerate_audit_triggers.sql`, `help_content_setup.sql`,
  `roster_setup.sql`) — when adding schema, create a new dated/descriptive `.sql` file rather than editing an
  existing one, since there's no migration history to replay.
- `app/config/config.php` (and environment variants `config_waw.php`, `config_wnh.php`, `bluehost/configbluehost.php`,
  etc.) hold DB credentials and site settings; the whole `app/config/**` tree is gitignored — don't expect it to be
  present or committed, and never commit real credentials into it.

## Deployment

Deployed via cPanel Git Version Control to `/home/sarum964/public_html/versions/stock/`, driven by `.cpanel.yml`
at repo root: `rsync` copies the working tree (excluding `.git*`, `.cpanel.yml`) then `chmod 755` dirs / `644` files
are applied explicitly (rsync-preserved permissions otherwise leave files unreadable by the web server). Flow:
commit → push to GitHub → cPanel "Update from Remote" (pull) → "Deploy HEAD Commit". Always pull before deploying;
edit `.cpanel.yml` locally and push it, never edit it directly on the server (that causes cPanel to flag
"uncommitted changes" and lock the deploy button).

## Framework architecture (`vendor/fw`, namespace `fw\`)

This is the part that isn't guessable from any single file — read this before touching request flow, DB access,
or adding a new Manager/Form/Table.

### Dependency injection: everything goes through `ClassFactory`
`fw\factory\ClassFactory::getClass($classname)` (`vendor/fw/factory/ClassFactory.php`) is the **only** place `new`
is allowed to appear in the codebase (stated as a hard rule in the file's header comment). It uses
`ReflectionMethod` on a class's `__construct()` and recursively instantiates every typed constructor parameter.
Classes use constructor-property-promotion everywhere (`App`, `RequestHandler`, managers, daemons) specifically so
the factory can auto-wire the whole object graph from type-hints alone. `index.php` / `daemon.php` each call
`getClass()` exactly once (`app\app` or `app\daemon\RosterReview`) and the factory builds everything transitively.
Autoloading is a hand-rolled PSR-4-ish loader (`vendor/fw/autoload/AutoLoader.php`, registered in
`vendor/fw/bootstrap/bootstrap.php`) mapping namespace prefixes to dirs: `app`→`app/`, `fw`→`vendor/fw/`,
`apptable`→`app/database/table/`, `lib`→`vendor/fw/library/` (this is why files do `use \lib\StdLib as lib;` —
`StdLib` is a framework-level debug helper (`lib::pr`, `lib::vd`, `lib::e`), not app code).

### Routing: page numbers, not URL paths
There's no path-based router. Every page has a numeric id (constants like `MenuManager::STOCKPAGE`, defined in
`app/controller/manager/MenuManager.php`); the requested page number comes from `$_REQUEST['p']` / session state,
resolved for access rights by `WebSession`. The actual "URL → code" manifest is a large
`switch ($this->pagenum)` in `app/controller/ViewController.php` (~60+ cases) where each case calls
`setthispage($id, $pagenum, $managercollection->XManager(), $formcollection->XForm(), ...)` to bind a
Manager+Form pair for that page. `ManagerCollection` / `FormCollection` are lazy (`??=`) registries — add a new
page by adding a case there plus wiring the Manager/Form into the collections.

AJAX requests are a separate path: `$_REQUEST['ajax']==1` with `action_id` selects a case in a big
`switch ($action)` inside `app/http/RequestHandler.php::processrequest()`, calling a Manager method directly and
returning JSON — this bypasses the page/form machinery entirely. Client JS matches this by posting
`action`/`action_id` + a data payload (see `app/assets/js/utils.js`, prefixed `fw_...` helpers).

### Table layer (`apptable` namespace, `app/database/table/*.php`)
Each table class extends `fw\database\table\MySQLTable` (base: `vendor/fw/database/table/Table.php` /
`MySQLTable.php`). The DB table name is derived by reflection from the class name — strip trailing `Table` and
convert CamelCase to underscores (`StockTable` → `stock`, via `lib::capsToUnderscores()`). The base class gives
generic CRUD (`insert`, `update`, `updateallfields`, `select*`, `delete`, `lock`/`unlock` via optimistic locking
columns `lockedby`/`locktime`) using parameterised queries (`execute_params`/`query_params`). Subclasses add
domain finder/report methods with descriptive lowercase verb names (`getstockwithlevels()`,
`getstocktakevariance()`, `calculateqoh()`), typically returning results/count by reference plus a bool success —
follow this naming style for new table methods rather than introducing camelCase.

### Manager layer (`app/controller/manager/*.php`)
Extends `fw\controller\manager\StdManager`, constructor-injects its table(s), and `init($session)` pulls
`db`/`user_id`/`requestdata` from the session. Provides generic CRUD (`getallrecords`, `getrecord`, `update`,
`insert`, `delete`) plus override points for CRUD forms: `setdefaults()`, `getparents()`, `updatechildren()`, and
n:n links via `$this->linkedobject` / `updaten2nlinks()`. Bespoke AJAX actions are just additional public methods
called directly from `RequestHandler`'s `action_id` switch (e.g. `StockEventManager::savemovement()`).

### Form layer (`app/view/form/*.php`)
Extends `fw\view\form\StdCRUDForm` (base `fw\view\form\Form`). `init($session)` pulls user/rights/pagenum context;
subclasses implement `initfields()` (defaults) and `buildinputs()` (HTML), using an injected `FormComponent`
helper (`buildinputrow()`, `buildselectrow()`, `rendersectionheading()`, etc.) to keep markup consistent across
forms. `Form` also emits a shared JS block (add/edit/validate/submit/multiselect wiring) that CRUD forms build on;
modal dialogs use the shared `$.cpdialog(...)` / `#volsdialog` jQuery pattern — see `feedback_volsdialog` note in
project memory for a known CSS sizing gotcha (`#volsdialog` needs `min-width:560px` or it overflows).

### Daemons
`daemon.php` is a thin CLI bootstrap mirroring `index.php` but calling `app\daemon\RosterReview` instead of
`app\App`. Daemon classes are still constructor-injected via `ClassFactory` but must manually repeat the
init sequence normally done by `RequestHandler` (`include APP_DIR.'database/dbconnection.php'`,
`connectandconfigure()`, `errorhandler->init()`, `db->init()`, `session->init()`) since they run outside the web
request flow. `RosterReview` is currently the only daemon; new daemons should follow the same manual-bootstrap
pattern rather than assuming `RequestHandler` has run.

## App structure by feature area

`app/controller/manager/`, `app/view/form/`, and `app/database/table/` each mirror the same feature groupings
1:1 (Manager ↔ Form ↔ Table per concept). Main groupings:
- **Stock/inventory**: Stock, StockCategory, StockClient, StockEvent(+Summary), StockSupplier(+Category),
  Stockout, Stocktake, DamagedStock, Delivery, Location — see `feedback`/`project_stock_module` memory for the
  full stock-event data model (QOH calc, stocktake/transfer close rules, uncontrolled-issue locations).
- **Reporting**: Report, BelowMinimumReport, DeliveriesReport, StockLevelReport, StockUsageReport,
  StocktakeVarianceReport.
- **Roster/booking**: Roster, RosterAdmin, RosterWizard, Task, TaskType, TaskExtender, Event, EventType — see
  `project_booking_alerts` memory for the `roster_alert` child-table feature.
- **Users/auth**: User, Role, Client, Session (login session — distinct from booking `Session`/`SessionTable`).
- **System/admin**: Config, Page, Action, Menu, Log, Help (see `project_help_system` memory), EMail, SupportMail.

`app/shared/` and `app/library/` are thin — most true shared utility code (`StdLib`) actually lives in the
framework at `vendor/fw/library/StdLib.php`, aliased everywhere as `use \lib\StdLib as lib;`.

`app/assets/js/utils.js` holds shared jQuery helpers (`fw_...` prefixed: CSV export via Blob, field validation)
used across forms; `app/assets/css/` is versioned per feature (e.g. `forms.0.2.css`) with an `earlier/` archive —
bump the version suffix rather than editing shared CSS in place if you need to avoid cache collisions.

No JS build step exists — `app/assets/js`/`css` are served as-is, no bundler/package.json.

## Related memory

Persistent project memory (audit triggers, help system, booking alerts, deployment gotchas, stock module data
model) is tracked outside this repo in Claude's memory system and referenced above by name — check it for
schema/business-rule details that go deeper than this file.
