<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Quotes\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\Organization;
use VentureDrake\LaravelCrm\Models\Person;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Services\QuoteService;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\QuoteResource;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

class EditQuote extends EditRecord
{
    use Concerns\HasQuoteAcceptAction;
    use Concerns\HasQuotePortalAction;
    use Concerns\HasQuoteRejectAction;
    use Concerns\HasQuoteSendAction;
    use Concerns\HasQuoteUnacceptAction;

    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->quoteAcceptAction(),
            $this->quoteRejectAction(),
            ...$this->quoteUnacceptActions(),
            Actions\ViewAction::make()
                ->button()
                ->hiddenLabel()
                ->icon('heroicon-m-eye'),
            $this->quoteSendAction(),
            $this->quotePortalAction(),
            Actions\DeleteAction::make()
                ->button()
                ->hiddenLabel()
                ->icon('heroicon-m-trash'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Quote $quote */
        $quote = $this->record;

        if (isset($data['subtotal']) && ! isset($data['sub_total'])) {
            $data['sub_total'] = $data['subtotal'];
        }

        foreach (['sub_total', 'discount', 'tax', 'total'] as $field) {
            $value = $data[$field] ?? null;
            if ($value !== null) {
                $data[$field] = $value / 100;
            }
        }

        $data['adjustment'] = isset($data['adjustments']) && $data['adjustments'] !== null
            ? $data['adjustments'] / 100
            : null;

        $data['products'] = $quote->quoteProducts
            ->map(fn ($line) => [
                'quote_product_id' => $line->id,
                'id' => $line->product_id,
                'quantity' => $line->quantity,
                'unit_price' => $line->price !== null ? $line->price / 100 : 0,
                'tax_amount' => $line->tax_amount !== null ? $line->tax_amount / 100 : 0,
                'amount' => $line->amount !== null ? $line->amount / 100 : 0,
                'comments' => $line->comments,
            ])
            ->all();

        if (empty($data['sub_total']) && ! empty($data['products'])) {
            $sub = 0;
            $tax = 0;
            foreach ($data['products'] as $p) {
                $sub += (float) ($p['amount'] ?? 0);
                $tax += (float) ($p['tax_amount'] ?? 0);
            }
            $data['sub_total'] = $sub;
            if (empty($data['tax'])) {
                $data['tax'] = $tax;
            }
            if (empty($data['total'])) {
                $discount = (float) ($data['discount'] ?? 0);
                $adjustment = (float) ($data['adjustment'] ?? 0);
                $data['total'] = max(0, $sub - $discount + $tax + $adjustment);
            }
        }

        $data['labels'] = $quote->labels()->pluck('crm_labels.id')->toArray();

        return QuoteResource::loadCrmCustomFieldsInto($data, $quote);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Quote $record */
        $person = isset($data['person_id'])
            ? Person::find($data['person_id'])
            : $record->person;
        $organization = isset($data['organization_id'])
            ? Organization::find($data['organization_id'])
            : $record->organization;

        app(QuoteService::class)->update(
            FormPayload::wrap($data),
            $record,
            $person,
            $organization,
            $record->client,
        );
        if (array_key_exists('labels', $data)) {
            $record->labels()->sync($data['labels'] ?? []);
        }
        QuoteResource::saveCrmCustomFields($data, $record);

        return $record->refresh();
    }

    protected function getAllRelationManagers(): array
    {
        return [];
    }
}
