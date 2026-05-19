<?php

namespace VentureDrake\LaravelCrmFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use VentureDrake\LaravelCrmFilament\Resources\Deals\DealResource;
use VentureDrake\LaravelCrmFilament\Resources\Leads\LeadResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\ChatWidgets\ChatWidgetResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\EmailTemplates\EmailTemplateResource;
use VentureDrake\LaravelCrmFilament\Resources\Chat\ChatConversationResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\FieldGroups\FieldGroupResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\SmsTemplates\SmsTemplateResource;
use VentureDrake\LaravelCrmFilament\Resources\EmailCampaigns\EmailCampaignResource;
use VentureDrake\LaravelCrmFilament\Resources\SmsCampaigns\SmsCampaignResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Fields\FieldResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Labels\LabelResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Roles\RoleResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\LeadSources\LeadSourceResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\PipelineStages\PipelineStageResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Pipelines\PipelineResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\ProductCategories\ProductCategoryResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\TaxRates\TaxRateResource;
use VentureDrake\LaravelCrmFilament\Clusters\Settings\Resources\Users\UserResource;
use VentureDrake\LaravelCrmFilament\Resources\Deliveries\DeliveryResource;
use VentureDrake\LaravelCrmFilament\Resources\Invoices\InvoiceResource;
use VentureDrake\LaravelCrmFilament\Resources\Products\ProductResource;
use VentureDrake\LaravelCrmFilament\Resources\PurchaseOrders\PurchaseOrderResource;
use VentureDrake\LaravelCrmFilament\Resources\Orders\OrderResource;
use VentureDrake\LaravelCrmFilament\Resources\Organizations\OrganizationResource;
use VentureDrake\LaravelCrmFilament\Resources\People\PersonResource;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\QuoteResource;
use VentureDrake\LaravelCrmFilament\Resources\Tasks\TaskResource;
use VentureDrake\LaravelCrmFilament\Widgets\CrmStatsOverview;
use VentureDrake\LaravelCrmFilament\Widgets\DealsValueStat;
use VentureDrake\LaravelCrmFilament\Widgets\MonthlyRevenueChart;
use VentureDrake\LaravelCrmFilament\Widgets\RecentActivityList;
use VentureDrake\LaravelCrmFilament\Widgets\TasksDueTodayList;
use VentureDrake\LaravelCrmFilament\Widgets\LeadsByStageChart;

class LaravelCrmPlugin implements Plugin
{
    /**
     * Optional module overrides. Null means "fall back to config('laravel-crm.modules')".
     *
     * @var array<string,bool>|null
     */
    protected ?array $modules = null;

    protected ?string $navigationGroup = null;

    protected ?string $brand = null;

    protected ?string $brandLogo = null;

    protected ?string $favicon = null;

    protected ?string $primaryColor = null;

    public function getId(): string
    {
        return 'laravel-crm';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    /**
     * @param  array<string,bool>  $modules
     */
    public function modules(array $modules): static
    {
        $this->modules = $modules;

        return $this;
    }

    public function withChat(bool $enabled = true): static
    {
        $this->modules['chat'] = $enabled;

        return $this;
    }

    public function withEmailMarketing(bool $enabled = true): static
    {
        $this->modules['email-marketing'] = $enabled;

        return $this;
    }

    public function withSmsMarketing(bool $enabled = true): static
    {
        $this->modules['sms-marketing'] = $enabled;

        return $this;
    }

    public function withXero(bool $enabled = true): static
    {
        $this->modules['xero'] = $enabled;

        return $this;
    }

    public function navigationGroup(string $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function brand(string $brand): static
    {
        $this->brand = $brand;

        return $this;
    }

    public function brandLogo(string $url): static
    {
        $this->brandLogo = $url;

        return $this;
    }

    public function favicon(string $url): static
    {
        $this->favicon = $url;

        return $this;
    }

    public function primaryColor(string $hex): static
    {
        $this->primaryColor = $hex;

        return $this;
    }

    public function isModuleEnabled(string $module): bool
    {
        if ($this->modules !== null && array_key_exists($module, $this->modules)) {
            return (bool) $this->modules[$module];
        }

        // Core CRM stores enabled modules as a flat array of slugs:
        //   config('laravel-crm.modules') === ['leads', 'deals', ...]
        return in_array($module, (array) config('laravel-crm.modules', []), true);
    }

    public function getNavigationGroup(): ?string
    {
        return $this->navigationGroup;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function register(Panel $panel): void
    {
        // Contact + activity entities are always available (no module flag in core).
        $resources = [
            PersonResource::class,
            OrganizationResource::class,
            TaskResource::class,
        ];

        // Pipeline / marketing entities are gated by core's `laravel-crm.modules` array.
        if ($this->isModuleEnabled('leads')) {
            $resources[] = LeadResource::class;
        }

        if ($this->isModuleEnabled('deals')) {
            $resources[] = DealResource::class;
        }

        if ($this->isModuleEnabled('quotes')) {
            $resources[] = QuoteResource::class;
        }

        if ($this->isModuleEnabled('orders')) {
            $resources[] = OrderResource::class;
        }

        if ($this->isModuleEnabled('invoices')) {
            $resources[] = InvoiceResource::class;
        }

        if ($this->isModuleEnabled('deliveries')) {
            $resources[] = DeliveryResource::class;
        }

        if ($this->isModuleEnabled('purchase-orders')) {
            $resources[] = PurchaseOrderResource::class;
        }

        // Products aren't a gated module in core; surface them whenever the panel runs.
        $resources[] = ProductResource::class;

        if ($this->isModuleEnabled('email-marketing')) {
            $resources[] = EmailCampaignResource::class;
        }

        if ($this->isModuleEnabled('sms-marketing')) {
            $resources[] = SmsCampaignResource::class;
        }

        if ($this->isModuleEnabled('chat')) {
            $resources[] = ChatConversationResource::class;
        }

        // Settings cluster lookups — always available; admins manage CRM-wide config.
        $resources[] = PipelineResource::class;
        $resources[] = PipelineStageResource::class;
        $resources[] = LabelResource::class;
        $resources[] = LeadSourceResource::class;
        $resources[] = TaxRateResource::class;
        $resources[] = ProductCategoryResource::class;
        $resources[] = FieldGroupResource::class;
        $resources[] = FieldResource::class;
        $resources[] = RoleResource::class;
        $resources[] = UserResource::class;
        if ($this->isModuleEnabled('email-marketing')) {
            $resources[] = EmailTemplateResource::class;
        }
        if ($this->isModuleEnabled('sms-marketing')) {
            $resources[] = SmsTemplateResource::class;
        }
        if ($this->isModuleEnabled('chat')) {
            $resources[] = ChatWidgetResource::class;
        }

        // Branding overrides: prefer plugin setters, fall back to laravel-crm settings.
        $settings = app()->bound('laravel-crm.settings') ? app('laravel-crm.settings') : null;
        $brandName = $this->brand ?? ($settings?->get('organization_name'));
        if ($brandName) {
            $panel->brandName($brandName);
        }
        $logo = $this->brandLogo ?? ($settings?->get('logo_file'));
        if ($logo) {
            $panel->brandLogo($logo);
        }
        if ($this->favicon) {
            $panel->favicon($this->favicon);
        }
        // Default to the core CRM's signature teal when the host hasn't set its own.
        $panelColor = $this->primaryColor ?? '#05b3a9';
        $panel->colors(['primary' => $panelColor]);

        $panel->resources($resources);

        $panel->discoverClusters(
            in: __DIR__.'/Clusters',
            for: 'VentureDrake\\LaravelCrmFilament\\Clusters',
        );

        $panel->widgets([
            CrmStatsOverview::class,
            DealsValueStat::class,
            LeadsByStageChart::class,
            MonthlyRevenueChart::class,
            TasksDueTodayList::class,
            RecentActivityList::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}








