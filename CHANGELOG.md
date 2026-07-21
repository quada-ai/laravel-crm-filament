# Changelog

All notable changes to `laravel-crm-filament` will be documented in this file.

The format is based on [Keep a Changelog 1.1.0](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning 2.0.0](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- Widened Filament constraints so the plugin now supports **Filament v4 alongside Filament v5** (`filament/filament`, `filament/forms`, `filament/tables` accept `^4.0 | ^5.0`). Existing v5 installs are unaffected; hosts on Filament v4 can install without upgrading.
- **Interactive install** — `php artisan laravelcrm:filament-install` now detects existing Filament panels and offers two install modes: publish a standalone `/crm` panel (Branch A) or inject the plugin into an existing panel (Branch B). Flags `--mode=crm|inject`, `--panel=<id>`, and `--force` bypass the prompts for CI. Branch A offers to append `LARAVEL_CRM_USER_INTERFACE=false` to `.env` so the Filament panel can take over `/crm` from the legacy CRM Livewire UI; Branch B aborts on resource-slug collisions between the host's panel and the plugin unless `--force` is passed.
- **Bootstraps `venturedrake/laravel-crm` if needed** — before publishing the panel, `laravelcrm:filament-install` checks whether the underlying CRM package has been installed (by looking for `config/laravel-crm.php`). If it hasn't, it offers to run `php artisan laravelcrm:install` for you so the panel isn't wired up over a half-installed package. Pass `--skip-crm-install` to bypass the check (useful for CI where the underlying install runs in a separate step).
- **`CrmPanelProvider` stub retemplated** — the published `CrmPanelProvider` now mounts at `/crm` (was `/admin`) and drops the `->discoverResources()` / `->discoverPages()` / `->discoverWidgets()` block, since the plugin registers everything via `LaravelCrmPlugin::register()`.

## [1.0.0] - 2026-07-16

### Added

- **Plugin entry point + install command**
  - `LaravelCrmPlugin` Filament panel plugin with fluent module flags (`->modules()`, `->withChat()`, `->withEmailMarketing()`, `->withSmsMarketing()`, `->withCustomers()`, `->withXero()`, `->navigationGroup()`, `->brand()`, `->brandLogo()`, `->favicon()`, `->primaryColor()`).
  - `php artisan laravelcrm:filament-install` command that publishes `app/Providers/Filament/CrmPanelProvider.php` (panel at `/admin`) and registers it in `bootstrap/providers.php`.

- **v0.5 — Pipeline conversion actions + PDF download**
  - **Quote → Order**, **Order → Invoice**, **Order → Delivery**, **Order → Purchase Order** conversion actions on the respective View pages, routed through the core CRM services (`OrderService`, `InvoiceService`, `DeliveryService`, `PurchaseOrderService`) so observers, audits, and Xero sync still fire.
  - Each conversion stamps the back-link FK (`quote.accepted_at`, `order.quote_id`, `invoice.order_id`, etc.), opens an in-app notification deep-linking to the new record, and hides itself once the downstream record exists.
  - Shared `Concerns\DownloadsPdf` trait powering both the `Send …` mail action and a standalone **Download PDF** header action on Quote / Invoice / Purchase Order View pages.

- **v0.6 — CSV bulk imports**
  - Header **Import CSV** action on People, Organizations, Products, and Users list pages.
  - File upload + header-row toggle, reactive column-mapping selects populated from uploaded CSV headers, dedupe field, chunk size for batch processing, and a **Download sample CSV** footer action streaming a UTF-8-BOM template.
  - Importers route through the core CRM services (`PersonService`, `OrganizationService`, `ProductService`) and respect the encryption-at-rest setting (`laravel-crm.encrypt_db_fields`).

- **v0.7 — Standalone activity/file resources + polymorphic Files RM**
  - Top-level read-only resources: **Notes**, **Calls**, **Meetings**, **Lunches**, **Files**, **Activities** — global lists across all parents with an **Open parent** record action deep-linking back to the owning resource.
  - **FilesRelationManager** added to every parent resource (Lead, Deal, Person, Organization, Quote, Order, Invoice, Purchase Order, Delivery). Uploads write a `File` model row with full metadata and log an entry on the parent's activity timeline.

- **v0.8 — Campaign send + per-recipient analytics + performance widgets**
  - **Send now** header action on Email + SMS Campaign View pages with a recipient-count confirmation modal.
  - **Performance** infolist section with sent / failed / skipped counts and open-rate / click-rate / unsubscribe-rate (email) or delivery-rate / click-rate / unsubscribe-rate (SMS).
  - Per-recipient RelationManager columns: `last_opened_at`, `first_clicked_at`, `bounce_status` (email); `delivered_at`, `clicksend_message_id` with copy-to-clipboard (SMS).
  - Footer **Sends over time** chart on each campaign View page (auto-hides for sub-hour spans).
  - Dashboard **CampaignPerformanceChart** widget for the last 5 sent email campaigns.

- **v0.9a — Customer resource + settings lookups**
  - **CustomerResource** (slug `customers`) — full CRUD with encrypted global search, Files RM, gated on the `customers` module (`->withCustomers()`).
  - Settings-cluster lookup resources: **Contact Types**, **Address Types**, **Organization Types**, **Industries**, **Timezones**, **Product Attributes** (List + Create + Edit).
  - **Industry** select on `OrganizationResource::form()`.
  - **ProductVariationsRelationManager** on the Product resource (name + description + attribute select).

- **v0.9b — Lead/pipeline lookups + Teams + Updates page**
  - **LeadStatus** and **PipelineStageProbability** lookup resources in the Settings cluster.
  - **`lead_status_id`** Select on the Lead form; **`pipeline_stage_probability_id`** Select on the Pipeline Stage form.
  - **CrmTeams** resource in the Settings cluster with a **TeamMembersRelationManager** for attaching multiple users via `crm_team_user`.
  - **Updates** page (Settings cluster) showing current vs latest version + a **Check for updates** action that queues `laravelcrm:update`.

- **v0.10 — Calendar + Task kanban + Reminders settings**
  - Standalone **Calendar** page rendering Tasks (by `due_at`) + Calls / Meetings / Lunches (by `start_at`) in a FullCalendar month/week grid. Drag-to-reschedule updates the underlying record and writes an activity row.
  - **Task Kanban** sub-resource page (Open / Today / Overdue / Completed columns) with drag-to-complete.
  - **Reminders** settings page — per-type (Task / Call / Meeting / Lunch) checkbox + `hours_before` input, persisted as user-scoped `Setting` rows.

- **v0.11 — Chat widget embed / portal preview / branded auth**
  - ChatWidget **View** page rendering the embed `<script>` snippet with copy-to-clipboard and a live `<iframe>` preview of the widget.
  - Quote / Invoice **Preview portal** action promoted to a primary header action.
  - Branded **Login** + **Profile** auth pages: avatar upload (persisted to `Setting`), section grouping, link to the Reminders settings, and panel-level brand pickup from `SettingService` (`organization_name`, `logo_file`, `primary_color`) in `CrmPanelProvider`.

- **Localization**
  - All user-visible Resource strings (form/column labels, section headings, action labels) routed through `__('laravel-crm-filament::labels.…')`.
  - Ships three locale files under `resources/lang/`: English (canonical), French (starter), Spanish (starter).
  - `labels.php` grouped into namespaces: `fields`, `contact`, `sales`, `money`, `campaign`, `chat`, `file`, `sections`, `actions`, `import`, `misc`.
  - Publishable via `php artisan vendor:publish --tag=laravel-crm-filament-translations`.

### Requirements

- PHP `^8.2`
- Laravel `^11.0 | ^12.0 | ^13.0` (`illuminate/contracts`)
- Filament `^4.0 | ^5.0` (`filament/filament`, `filament/forms`, `filament/tables`)
- `venturedrake/laravel-crm` `^2.0`

[1.0.0]: https://github.com/venturedrake/laravel-crm-filament/releases/tag/v1.0.0
