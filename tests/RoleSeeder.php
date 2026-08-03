<?php

namespace VentureDrake\LaravelCrmFilament\Tests;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds Spatie roles + permissions used by the core CRM policies.
 * Mirrors the role / permission matrix from
 * core's LaravelCrmTablesSeeder so policy tests can assign a
 * realistic role to a User and assert the resulting authorization
 * matrix on every Filament Resource.
 */
class RoleSeeder
{
    /** @var list<string> */
    public const ENTITIES = [
        'leads',
        'deals',
        'quotes',
        'orders',
        'invoices',
        'deliveries',
        'purchase orders',
        'people',
        'organizations',
        'contacts',
        'activities',
        'tasks',
        'notes',
        'calls',
        'meetings',
        'lunches',
        'files',
        'pipelines',
        'email-campaigns',
        'email-templates',
        'sms-campaigns',
        'sms-templates',
        'customers',
        'products',
        'teams',
        'features',
        'monitors',
        'tax rates',
        'roles',
    ];

    /** @var list<string> */
    public const ROLES = ['Owner', 'Admin', 'Manager', 'Employee'];

    public static function seed(?string $guardName = null): void
    {
        $guardName = $guardName ?? config('auth.defaults.guard', 'web');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::ENTITIES as $entity) {
            foreach (['create', 'view', 'edit', 'delete'] as $action) {
                Permission::findOrCreate("{$action} crm {$entity}", $guardName);
            }
        }

        // Chat / chat-widget perms used by ChatConversationPolicy +
        // ChatWidgetPolicy.
        foreach (['view crm chat', 'reply crm chat', 'delete crm chat', 'manage crm chat widgets'] as $perm) {
            Permission::findOrCreate($perm, $guardName);
        }

        // Settings perms used by SettingPolicy / RolePolicy /
        // PermissionPolicy / UserPolicy.
        foreach (['view crm settings', 'edit crm settings', 'create crm users', 'view crm users', 'edit crm users', 'delete crm users', 'manage crm feature statuses'] as $perm) {
            Permission::findOrCreate($perm, $guardName);
        }

        $allPerms = Permission::where('guard_name', $guardName)->get();

        $owner = Role::findOrCreate('Owner', $guardName)->givePermissionTo($allPerms);
        $admin = Role::findOrCreate('Admin', $guardName)->givePermissionTo($allPerms);

        $managerAndEmployeePerms = Permission::where('guard_name', $guardName)
            ->whereNotIn('name', ['create crm users', 'edit crm users', 'delete crm users'])
            ->get();

        Role::findOrCreate('Manager', $guardName)->givePermissionTo($managerAndEmployeePerms);
        Role::findOrCreate('Employee', $guardName)->givePermissionTo($managerAndEmployeePerms);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
