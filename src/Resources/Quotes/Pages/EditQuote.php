<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Quotes\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use VentureDrake\LaravelCrm\Models\Quote;
use VentureDrake\LaravelCrm\Services\QuoteService;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\QuoteResource;
use VentureDrake\LaravelCrmFilament\Support\FormPayload;

class EditQuote extends EditRecord
{
    use Concerns\HasQuoteSendAction;
    use Concerns\HasQuotePortalAction;

    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            $this->quoteSendAction(),
            $this->quotePortalAction(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Quote $quote */
        $quote = $this->record;

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
                'amount' => $line->amount !== null ? $line->amount / 100 : 0,
                'comments' => $line->comments,
            ])
            ->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Quote $record */
        app(QuoteService::class)->update(
            FormPayload::wrap($data),
            $record,
            $record->person,
            $record->organization,
            $record->client,
        );

        return $record->refresh();
    }
}
