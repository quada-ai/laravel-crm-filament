# Laravel CRM — Filament Panel Plugin

A native Filament v5 panel plugin for [`venturedrake/laravel-crm`](https://github.com/venturedrake/laravel-crm). Wraps the existing CRM domain layer (models, services, observers, policies, encryption, audit) in Filament Resources, Clusters, Pages, and Widgets so the same database can be administered via Filament alongside (or instead of) the legacy `/crm` MaryUI/Livewire interface.

## Requirements

- PHP `^8.2`
- Laravel `^11.0 | ^12.0 | ^13.0`
- Filament `^5.0`
- `venturedrake/laravel-crm` `^2.0`

## Installation

```bash
composer require venturedrake/laravel-crm-filament
php artisan laravelcrm:filament-install
```

The install command publishes `app/Providers/Filament/CrmPanelProvider.php` (panel at `/admin`) and registers it in `bootstrap/providers.php`.

Add `Filament\Models\Contracts\FilamentUser` to `App\Models\User` and implement `canAccessPanel()`:

```php
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use VentureDrake\LaravelCrm\Traits\HasCrmAccess;

class User extends Authenticatable implements FilamentUser
{
    use HasCrmAccess;

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasCrmAccess();
    }
}
```

## Registering the plugin

```php
use VentureDrake\LaravelCrmFilament\LaravelCrmPlugin;

->plugin(
    LaravelCrmPlugin::make()
        // ->modules(['leads' => true, 'deals' => true, /* ... */])
        // ->withChat()
        // ->withEmailMarketing()
        // ->withSmsMarketing()
        // ->withXero()
        // ->navigationGroup('CRM')
        // ->brand('Acme CRM')
        // ->brandLogo('https://example.com/logo.svg')
        // ->favicon('https://example.com/favicon.ico')
        // ->primaryColor('#05b3a9')
)
```

By default the plugin reads `config('laravel-crm.modules')` to decide which gated resources to register. Use `->modules([...])` to override per-panel.

If no `brand()` / `brandLogo()` is set the plugin falls back to the core CRM's `laravel-crm.settings`: `organization_name` and `logo_file`. If no `primaryColor()` is set the panel defaults to `#05b3a9` (the CRM's teal accent).

## What's in the panel

**Main panel resources** (gated on `config('laravel-crm.modules')`):

| Resource | Slug | Module gate |
|---|---|---|
| Lead | `/admin/leads` | `leads` |
| Deal | `/admin/deals` | `deals` |
| Quote | `/admin/quotes` | `quotes` |
| Order | `/admin/orders` | `orders` |
| Invoice | `/admin/invoices` | `invoices` |
| Purchase Order | `/admin/purchase-orders` | `purchase-orders` |
| Delivery | `/admin/deliveries` | `deliveries` |
| Email Campaign | `/admin/email-campaigns` | `email-marketing` |
| SMS Campaign | `/admin/sms-campaigns` | `sms-marketing` |
| Chat | `/admin/chat` | `chat` |
| Person | `/admin/people` | always |
| Organization | `/admin/organizations` | always |
| Task | `/admin/tasks` | always |
| Product | `/admin/products` | always |

**Dashboard widgets**: open leads / open deals / tasks due today + open-leads-by-stage chart.

**Settings cluster** at `/admin/settings`:

- Pipelines, Pipeline Stages, Labels, Lead Sources, Tax Rates, Product Categories
- Field Groups + Fields (custom field definitions, including option lists and per-model scoping)
- Roles (Spatie\Permission, with Owner/Admin protected from edit/delete)
- Email Templates, SMS Templates, Chat Widgets
- General settings page (key/value via `SettingService`)
- Integrations page (Xero connect/disconnect + sync toggles, ClickSend status)

**RelationManagers**: Notes, Tasks, Calls, Meetings inline on Lead / Deal / Person / Organization edit pages (polymorphic via `HasCrmActivities`). Each new entry logs to the core CRM `Activity` table for the timeline feed. Email/SMS campaign view pages get a per-recipient RelationManager showing per-row send/open/click/unsubscribe state.

**Per-resource actions**:

- Quote / Invoice / Purchase Order: **Send** (generates dompdf PDF, sends signed-portal mailable via the core's `Mail\SendQuote` / `SendInvoice` / `SendPurchaseOrder`)
- Quote / Invoice: **Open portal** (jumps to `/p/quotes/...` or `/p/invoices/...`)
- Email Campaign: **Preview** (renders `EmailCampaignMessage::renderPreview()` in a modal), **Schedule** (datetime modal → `service->schedule()`), **Cancel**
- SMS Campaign: **Preview** (rendered body + segment count via `SmsCampaignMessage::renderPreview()` / `::segmentCount()`), **Schedule**, **Cancel`. Body Textarea on the form shows a live segment-count estimate via `helperText`.
- Chat: **Reply**, **Close conversation**, **Convert to lead** (creates Person + Lead from visitor); thread view subscribes to `echo:crm-chat.{external_id},.chat.message` for realtime message refresh when Laravel Echo is configured.

## Custom fields

Models with the core's `HasCrmFields` trait (Lead, Deal, Quote, Order, Invoice, PurchaseOrder, Person, Organization, Task, Product) automatically get a "Custom fields" section in their Filament forms when `Field` rows are scoped to the model via `FieldModel`. The plugin's `Concerns\HasCrmCustomFields` trait handles:

- Mapping `Field::type` (text / textarea / date / checkbox / select / select_multiple / radio / checkbox_multiple) to the right Filament component
- Loading `FieldValue` rows on edit
- Saving `FieldValue` rows on create / update via `updateOrCreate`

Define fields via the Settings cluster (`/admin/settings/fields`).

## Coexistence

The plugin doesn't touch the core CRM's `/crm` Livewire UI. Both UIs run side-by-side against the same database. Disabling the legacy UI (if you only want Filament) is host-side configuration outside the plugin's scope.

## Testing

```bash
composer test
```

74 Pest tests cover routing, model binding, cluster wiring, RelationManager attachment, custom-fields trait integration, plugin module gating, branding setters, and role protection.

## License

MIT — same as the core CRM package.
