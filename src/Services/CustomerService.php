<?php

namespace VentureDrake\LaravelCrmFilament\Services;

use VentureDrake\LaravelCrm\Models\Customer;

/**
 * Local CustomerService shim — core CRM does not yet ship one (CustomerController
 * persists directly). Mirrors the $request-fluent signature of the other core
 * services so call sites can route through ::create/::update via FormPayload.
 * Swap this out for a core service when one lands upstream.
 */
class CustomerService
{
    public function create($request)
    {
        return Customer::create([
            'name' => $request->name,
            'user_owner_id' => $request->user_owner_id,
        ]);
    }

    public function update(Customer $customer, $request)
    {
        $customer->update([
            'name' => $request->name,
            'user_owner_id' => $request->user_owner_id,
        ]);

        return $customer;
    }
}
