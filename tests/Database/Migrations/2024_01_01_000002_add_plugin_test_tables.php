<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supplements TestSchema with the tables the Filament plugin's
 * resources reference but the core CRM's TestSchema doesn't ship —
 * primarily auxiliary lookup, communication, and pipeline-conversion
 * tables exposed in v0.x of the plugin (Lunches, Meetings, Calls,
 * Deliveries, Purchase Orders, Files, lookup tables, etc.).
 */
return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('laravel-crm.db_table_prefix');

        if (! Schema::hasTable($prefix . 'industries')) {
            Schema::create($prefix . 'industries', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable($prefix . 'contact_types')) {
            Schema::create($prefix . 'contact_types', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable($prefix . 'address_types')) {
            Schema::create($prefix . 'address_types', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable($prefix . 'organization_types')) {
            Schema::create($prefix . 'organization_types', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable($prefix . 'timezones')) {
            Schema::create($prefix . 'timezones', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('offset')->nullable();
                $table->string('diff_from_gtm')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable($prefix . 'product_attributes')) {
            Schema::create($prefix . 'product_attributes', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable($prefix . 'pipeline_stage_probabilities')) {
            Schema::create($prefix . 'pipeline_stage_probabilities', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('external_id')->nullable();
                $table->string('name');
                $table->integer('percent')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('teams')) {
            Schema::create('teams', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id');
                $table->string('name');
                $table->boolean('personal_team')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable($prefix . 'team_user')) {
            Schema::create($prefix . 'team_user', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('team_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable($prefix . 'calls')) {
            Schema::create($prefix . 'calls', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('external_id')->nullable();
                $table->morphs('callable');
                $table->text('description')->nullable();
                $table->dateTime('start_at')->nullable();
                $table->dateTime('finish_at')->nullable();
                $table->dateTime('called_at')->nullable();
                $table->boolean('completed')->default(false);
                $table->unsignedBigInteger('user_assigned_id')->nullable();
                $table->unsignedBigInteger('user_created_id')->nullable();
                $table->unsignedBigInteger('user_updated_id')->nullable();
                $table->unsignedBigInteger('team_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable($prefix . 'meetings')) {
            Schema::create($prefix . 'meetings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('external_id')->nullable();
                $table->morphs('meetingable');
                $table->string('name')->nullable();
                $table->text('description')->nullable();
                $table->dateTime('start_at')->nullable();
                $table->dateTime('finish_at')->nullable();
                $table->string('location')->nullable();
                $table->boolean('completed')->default(false);
                $table->unsignedBigInteger('user_assigned_id')->nullable();
                $table->unsignedBigInteger('user_created_id')->nullable();
                $table->unsignedBigInteger('user_updated_id')->nullable();
                $table->unsignedBigInteger('team_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable($prefix . 'lunches')) {
            Schema::create($prefix . 'lunches', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('external_id')->nullable();
                $table->morphs('lunchable');
                $table->string('name')->nullable();
                $table->text('description')->nullable();
                $table->dateTime('start_at')->nullable();
                $table->dateTime('finish_at')->nullable();
                $table->string('location')->nullable();
                $table->boolean('completed')->default(false);
                $table->unsignedBigInteger('user_assigned_id')->nullable();
                $table->unsignedBigInteger('user_created_id')->nullable();
                $table->unsignedBigInteger('user_updated_id')->nullable();
                $table->unsignedBigInteger('team_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable($prefix . 'deliveries')) {
            Schema::create($prefix . 'deliveries', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('external_id')->nullable();
                $table->string('delivery_id')->nullable();
                $table->string('prefix')->nullable();
                $table->unsignedInteger('number')->nullable();
                $table->unsignedBigInteger('order_id')->nullable();
                $table->unsignedBigInteger('person_id')->nullable();
                $table->unsignedBigInteger('organization_id')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->date('delivery_date')->nullable();
                $table->date('delivered_at')->nullable();
                $table->text('notes')->nullable();
                $table->string('status')->nullable();
                $table->unsignedBigInteger('user_created_id')->nullable();
                $table->unsignedBigInteger('user_updated_id')->nullable();
                $table->unsignedBigInteger('user_assigned_id')->nullable();
                $table->unsignedBigInteger('team_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable($prefix . 'delivery_products')) {
            Schema::create($prefix . 'delivery_products', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('external_id')->nullable();
                $table->unsignedBigInteger('delivery_id');
                $table->unsignedBigInteger('product_id')->nullable();
                $table->unsignedBigInteger('order_product_id')->nullable();
                $table->integer('quantity')->default(1);
                $table->text('comments')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable($prefix . 'purchase_orders')) {
            Schema::create($prefix . 'purchase_orders', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('external_id')->nullable();
                $table->string('purchase_order_id')->nullable();
                $table->string('prefix')->nullable();
                $table->unsignedInteger('number')->nullable();
                $table->unsignedBigInteger('order_id')->nullable();
                $table->unsignedBigInteger('person_id')->nullable();
                $table->unsignedBigInteger('organization_id')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->date('issue_date')->nullable();
                $table->date('expected_at')->nullable();
                $table->date('delivered_at')->nullable();
                $table->unsignedBigInteger('currency')->nullable();
                $table->unsignedBigInteger('sub_total')->nullable();
                $table->unsignedBigInteger('discount')->nullable();
                $table->unsignedBigInteger('tax')->nullable();
                $table->unsignedBigInteger('total')->nullable();
                $table->string('reference')->nullable();
                $table->text('notes')->nullable();
                $table->text('terms')->nullable();
                $table->string('delivery_type')->nullable();
                $table->string('status')->nullable();
                $table->unsignedBigInteger('user_created_id')->nullable();
                $table->unsignedBigInteger('user_updated_id')->nullable();
                $table->unsignedBigInteger('user_owner_id')->nullable();
                $table->unsignedBigInteger('user_assigned_id')->nullable();
                $table->unsignedBigInteger('team_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable($prefix . 'purchase_order_lines')) {
            Schema::create($prefix . 'purchase_order_lines', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('external_id')->nullable();
                $table->unsignedBigInteger('purchase_order_id');
                $table->unsignedBigInteger('product_id')->nullable();
                $table->integer('quantity')->default(1);
                $table->decimal('unit_price', 15, 2)->nullable();
                $table->decimal('amount', 15, 2)->nullable();
                $table->string('currency')->nullable();
                $table->decimal('discount', 15, 2)->nullable();
                $table->decimal('tax', 15, 2)->nullable();
                $table->decimal('price', 15, 2)->nullable();
                $table->decimal('cost_per_unit', 15, 2)->nullable();
                $table->unsignedBigInteger('tax_rate_id')->nullable();
                $table->text('comments')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable($prefix . 'files')) {
            Schema::create($prefix . 'files', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('external_id')->nullable();
                $table->morphs('fileable');
                $table->string('filename');
                $table->string('original_filename')->nullable();
                $table->string('disk')->nullable();
                $table->string('mime')->nullable();
                $table->string('file_type')->nullable();
                $table->string('format')->nullable();
                $table->unsignedBigInteger('filesize')->nullable();
                $table->string('title')->nullable();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('user_created_id')->nullable();
                $table->unsignedBigInteger('user_updated_id')->nullable();
                $table->unsignedBigInteger('team_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable($prefix . 'chat_widgets')) {
            Schema::create($prefix . 'chat_widgets', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('external_id')->nullable();
                $table->string('public_key')->unique();
                $table->string('name');
                $table->text('allowed_origins')->nullable();
                $table->string('primary_color')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('user_created_id')->nullable();
                $table->unsignedBigInteger('user_updated_id')->nullable();
                $table->unsignedBigInteger('team_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable($prefix . 'chat_conversations')) {
            Schema::create($prefix . 'chat_conversations', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('external_id')->nullable();
                $table->string('chat_id')->nullable();
                $table->unsignedBigInteger('team_id')->nullable();
                $table->unsignedBigInteger('chat_widget_id')->nullable();
                $table->unsignedBigInteger('chat_visitor_id')->nullable();
                $table->unsignedBigInteger('lead_id')->nullable();
                $table->string('subject')->nullable();
                $table->string('status')->nullable();
                $table->unsignedBigInteger('user_assigned_id')->nullable();
                $table->timestamp('last_message_at')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->unsignedBigInteger('user_created_id')->nullable();
                $table->unsignedBigInteger('user_updated_id')->nullable();
                $table->unsignedBigInteger('user_deleted_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable($prefix . 'chat_visitors')) {
            Schema::create($prefix . 'chat_visitors', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('external_id')->nullable();
                $table->unsignedBigInteger('team_id')->nullable();
                $table->unsignedBigInteger('chat_widget_id')->nullable();
                $table->string('visitor_token', 64)->nullable()->unique();
                $table->string('name')->nullable();
                $table->string('email')->nullable();
                $table->string('ip_address', 64)->nullable();
                $table->string('user_agent', 512)->nullable();
                $table->string('current_url', 1024)->nullable();
                $table->string('country_code', 8)->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->unsignedBigInteger('person_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable($prefix . 'chat_messages')) {
            Schema::create($prefix . 'chat_messages', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('external_id')->nullable();
                $table->unsignedBigInteger('team_id')->nullable();
                $table->unsignedBigInteger('chat_conversation_id');
                $table->string('sender_type')->nullable();
                $table->unsignedBigInteger('sender_id')->nullable();
                $table->text('body');
                $table->timestamp('read_at')->nullable();
                $table->timestamp('visitor_read_at')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable($prefix . 'email_campaign_recipients') && ! Schema::hasColumn($prefix . 'email_campaign_recipients', 'tracking_token')) {
            Schema::table($prefix . 'email_campaign_recipients', function (Blueprint $table) {
                $table->string('tracking_token', 64)->nullable()->index();
            });
        }

        if (Schema::hasTable($prefix . 'sms_campaign_recipients') && ! Schema::hasColumn($prefix . 'sms_campaign_recipients', 'tracking_token')) {
            Schema::table($prefix . 'sms_campaign_recipients', function (Blueprint $table) {
                $table->string('tracking_token', 64)->nullable()->index();
            });
        }

        if (Schema::hasTable($prefix . 'email_campaign_recipients') && ! Schema::hasColumn($prefix . 'email_campaign_recipients', 'error')) {
            Schema::table($prefix . 'email_campaign_recipients', function (Blueprint $table) {
                $table->text('error')->nullable();
            });
        }

        if (Schema::hasTable($prefix . 'sms_campaign_recipients') && ! Schema::hasColumn($prefix . 'sms_campaign_recipients', 'error')) {
            Schema::table($prefix . 'sms_campaign_recipients', function (Blueprint $table) {
                $table->text('error')->nullable();
            });
        }

        if (! Schema::hasTable($prefix . 'email_campaign_clicks')) {
            Schema::create($prefix . 'email_campaign_clicks', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('email_campaign_recipient_id')->index();
                $table->text('original_url');
                $table->timestamp('clicked_at')->nullable();
                $table->string('ip', 64)->nullable();
                $table->string('user_agent', 512)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable($prefix . 'sms_campaign_clicks')) {
            Schema::create($prefix . 'sms_campaign_clicks', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('sms_campaign_recipient_id')->index();
                $table->text('original_url');
                $table->timestamp('clicked_at')->nullable();
                $table->string('ip', 64)->nullable();
                $table->string('user_agent', 512)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('audits')) {
            Schema::create('audits', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('user_type')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('event');
                $table->morphs('auditable');
                $table->text('old_values')->nullable();
                $table->text('new_values')->nullable();
                $table->text('url')->nullable();
                $table->ipAddress('ip_address')->nullable();
                $table->string('user_agent', 1023)->nullable();
                $table->string('tags')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'user_type']);
            });
        }

        if (! Schema::hasTable($prefix . 'xero_contacts')) {
            Schema::create($prefix . 'xero_contacts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('external_id')->nullable();
                $table->unsignedBigInteger('team_id')->index()->nullable();
                $table->unsignedBigInteger('organization_id')->nullable();
                $table->string('contact_id')->nullable();
                $table->string('name')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable($prefix . 'xero_items')) {
            Schema::create($prefix . 'xero_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('external_id')->nullable();
                $table->unsignedBigInteger('team_id')->index()->nullable();
                $table->unsignedBigInteger('product_id')->nullable();
                $table->string('item_id')->nullable();
                $table->string('code')->nullable();
                $table->string('name')->nullable();
                $table->boolean('inventory_tracked')->default(false);
                $table->boolean('is_purchased')->default(false);
                $table->integer('purchase_price')->nullable();
                $table->string('purchase_description')->nullable();
                $table->boolean('is_sold')->default(false);
                $table->integer('sell_price')->nullable();
                $table->string('sell_description')->nullable();
                $table->integer('quantity_on_hand')->nullable();
                $table->dateTime('updated_date')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable($prefix . 'xero_invoices')) {
            Schema::create($prefix . 'xero_invoices', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('external_id')->nullable();
                $table->unsignedBigInteger('team_id')->index()->nullable();
                $table->unsignedBigInteger('invoice_id')->nullable();
                $table->string('xero_type')->nullable();
                $table->string('xero_id')->nullable();
                $table->string('number')->nullable();
                $table->string('reference')->nullable();
                $table->integer('subtotal')->nullable();
                $table->integer('total_tax')->nullable();
                $table->integer('total')->nullable();
                $table->string('status')->nullable();
                $table->integer('amount_due')->nullable();
                $table->integer('amount_paid')->nullable();
                $table->integer('amount_credited')->nullable();
                $table->date('issue_date')->nullable();
                $table->date('due_date')->nullable();
                $table->string('line_amount_types')->nullable();
                $table->string('currency_code', 3)->nullable();
                $table->dateTime('fully_paid_at')->nullable();
                $table->dateTime('xero_updated_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable($prefix . 'xero_purchase_orders')) {
            Schema::create($prefix . 'xero_purchase_orders', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('external_id')->nullable();
                $table->unsignedBigInteger('team_id')->index()->nullable();
                $table->unsignedBigInteger('purchase_order_id')->nullable();
                $table->string('xero_type')->nullable();
                $table->string('xero_id')->nullable();
                $table->string('number')->nullable();
                $table->string('reference')->nullable();
                $table->integer('subtotal')->nullable();
                $table->integer('total_tax')->nullable();
                $table->integer('total')->nullable();
                $table->string('status')->nullable();
                $table->date('issue_date')->nullable();
                $table->date('delivery_date')->nullable();
                $table->string('line_amount_types')->nullable();
                $table->string('currency_code', 3)->nullable();
                $table->dateTime('xero_updated_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'two_factor_secret')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('two_factor_secret')->nullable();
                $table->text('two_factor_recovery_codes')->nullable();
                $table->timestamp('two_factor_confirmed_at')->nullable();
            });
        }

        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    public function down(): void
    {
        // No-op: the test database is dropped between test runs.
    }
};
