<?php

/**
 * Translation strings for the Filament CRM panel.
 *
 * Override in a host app by publishing this file with:
 *
 *   php artisan vendor:publish --tag=laravel-crm-filament-translations
 *
 * Then edit `lang/vendor/laravel-crm-filament/{locale}/labels.php`.
 */
return [
    // Generic field / column labels
    'fields' => [
        'id' => 'ID',
        'name' => 'Name',
        'description' => 'Description',
        'type' => 'Type',
        'status' => 'Status',
        'category' => 'Category',
        'owner' => 'Owner',
        'created_by' => 'Created by',
        'created_at' => 'Created at',
        'updated_at' => 'Updated at',
        'assigned_to' => 'Assigned to',
        'assigned' => 'Assigned',
        'assignee' => 'Assignee',
        'by_user' => 'By user',
        'by' => 'By',
        'when' => 'When',
        'action' => 'Action',
        'on' => 'On',
        'record' => 'Record',
        'record_type' => 'Record type',
        'parent' => 'Parent',
        'parent_type' => 'Parent type',
        'linked_to' => 'Linked to',
        'user' => 'User',
        'group' => 'Group',
        'order' => 'Order',
        'color' => 'Color',
        'active' => 'Active',
        'system' => 'System',
        'verified' => 'Verified',
        'primary' => 'Primary',
        'roles' => 'Roles',
        'permissions' => 'Permissions',
        'members' => 'Members',
        'options' => 'Options',
        'applies_to' => 'Applies to',
        'available_on' => 'Available on',
        'template' => 'Template',
        'last_activity' => 'Last activity',
        'fields' => 'Fields',
        'labels' => 'Labels',
        'timestamp' => 'Timestamp',
    ],

    // Contact / address fields
    'contact' => [
        'phone_numbers' => 'Phone numbers',
        'phone' => 'Phone',
        'number' => 'Number',
        'email_addresses' => 'Email addresses',
        'email' => 'Email',
        'addresses' => 'Addresses',
        'line1' => 'Line 1',
        'line2' => 'Line 2',
        'line3' => 'Line 3',
        'city' => 'City',
        'state' => 'State / Province',
        'post_code' => 'Post code',
        'postal_code' => 'Postal code',
        'country' => 'Country',
    ],

    // Sales / pipeline
    'sales' => [
        'leads' => 'Leads',
        'lead_source' => 'Lead source',
        'lead_status' => 'Lead status',
        'pipeline' => 'Pipeline',
        'pipeline_stage' => 'Pipeline stage',
        'stage' => 'Stage',
        'stages' => 'Stages',
        'probability' => 'Probability',
        'probability_percent' => 'Probability %',
        'expected_close' => 'Expected close',
    ],

    // Money / orders / lines
    'money' => [
        'subtotal' => 'Subtotal',
        'tax_rate' => 'Tax rate',
        'default_tax_rate' => 'Default tax rate',
        'unit_price' => 'Unit price',
        'line_items' => 'Line items',
        'products' => 'Products',
        'product' => 'Product',
        'sku' => 'SKU',
        'barcode' => 'Barcode (ISBN/UPC/GTIN)',
        'industry' => 'Industry',
        'employees' => 'Employees',
        'person' => 'Person',
        'issue_date' => 'Issue date',
        'expiry_date' => 'Expiry date',
        'expires' => 'Expires',
        'due_date' => 'Due date',
        'due_at' => 'Due at',
        'due' => 'Due',
        'start' => 'Start',
        'finish' => 'Finish',
        'completed' => 'Completed',
        'done' => 'Done',
        'order' => 'Order',
        'order_line' => 'Order line',
        'items_delivered' => 'Items delivered',
        'expected' => 'Expected',
        'delivered' => 'Delivered',
        'delivered_on' => 'Delivered on',
        'delivery_date' => 'Delivery date',
        'delivery_addresses' => 'Delivery addresses',
    ],

    // Campaigns / messaging
    'campaign' => [
        'recipients' => 'Recipients',
        'send_at' => 'Send at',
        'sent' => 'Sent',
        'failed' => 'Failed',
        'skipped' => 'Skipped',
        'opens' => 'Opens',
        'clicks' => 'Clicks',
        'open_rate' => 'Open rate',
        'click_rate' => 'Click rate',
        'unsubscribe_rate' => 'Unsubscribe rate',
        'delivery_rate' => 'Delivery rate',
        'unsubscribed' => 'Unsubscribed',
        'first_clicked' => 'First clicked',
        'last_opened' => 'Last opened',
        'bounce' => 'Bounce',
        'message_id' => 'Message ID',
        'from' => 'From',
        'to' => 'To',
        'send_me_a_copy' => 'Send me a copy',
        'schedule_for' => 'Schedule for',
    ],

    // Chat
    'chat' => [
        'conversations' => 'Conversations',
        'visitor' => 'Visitor',
        'public_key' => 'Public key',
        'allowed_origins' => 'Allowed origins',
    ],

    // File / upload
    'file' => [
        'file' => 'File',
        'file_type' => 'File type',
        'uploaded_by' => 'Uploaded by',
        'avatar' => 'Avatar',
    ],

    // Audit / history (owen-it/laravel-auditing)
    'audit' => [
        'history' => 'History',
        'event' => 'Event',
        'user' => 'User',
        'changes' => 'Changes',
    ],

    // Sections (top-level grouping headings on forms / infolists)
    'sections' => [
        'custom_fields' => 'Custom fields',
        'performance' => 'Performance',
        'column_mapping' => 'Column mapping',
        'xero' => 'Xero',
        'clicksend' => 'ClickSend (SMS)',
        'avatar' => 'Avatar',
        'account' => 'Account',
        'notification_preferences' => 'Notification preferences',
        'branding' => 'Branding',
        'formatting' => 'Formatting',
        'prefixes' => 'Prefixes',
        'tax' => 'Tax',
    ],

    // Actions
    'actions' => [
        'save' => 'Save',
        'save_sync_settings' => 'Save sync settings',
        'cancel' => 'Cancel',
        'close' => 'Close',
        'close_conversation' => 'Close conversation',
        'bulk_close' => 'Bulk close',
        'reply' => 'Reply',
        'preview' => 'Preview',
        'preview_portal' => 'Preview portal',
        'send' => 'Send',
        'send_quote' => 'Send quote',
        'send_invoice' => 'Send invoice',
        'send_purchase_order' => 'Send PO',
        'send_now' => 'Send now',
        'schedule' => 'Schedule',
        'convert_to_lead' => 'Convert to lead',
        'convert_to_order' => 'Convert to order',
        'convert_to_invoice' => 'Convert to invoice',
        'convert_to_delivery' => 'Convert to delivery',
        'convert_to_purchase_order' => 'Convert to purchase order',
        'open_invoice' => 'Open invoice',
        'open_order' => 'Open order',
        'open_delivery' => 'Open delivery',
        'open_purchase_order' => 'Open purchase order',
        'open_parent' => 'Open parent',
        'mark_complete' => 'Mark complete',
        'kanban' => 'Kanban',
        'download' => 'Download',
        'download_pdf' => 'Download PDF',
        'download_sample_csv' => 'Download sample CSV',
        'check_for_updates' => 'Check for updates',
        'connect_xero' => 'Connect Xero',
        'disconnect_xero' => 'Disconnect Xero',
    ],

    // CSV import
    'import' => [
        'csv_file' => 'CSV file',
        'csv_has_header_row' => 'CSV has a header row',
        'chunk_size' => 'Chunk size',
        'skip_duplicates_by' => 'Skip duplicates by',
    ],

    // Misc / settings
    'misc' => [
        'crm' => 'CRM',
        'crm_access' => 'CRM access',
        'has_crm_access' => 'Has CRM access',
        'hours_before' => 'Hours before',
        'attribute' => 'Attribute',
        'diff_from_gmt' => 'Diff from GMT',
    ],
];
