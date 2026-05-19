<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Quotes\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\QuoteResource;

class ViewQuote extends ViewRecord
{
    use Concerns\HasQuoteConvertToOrderAction;
    use Concerns\HasQuotePortalAction;
    use Concerns\HasQuoteSendAction;

    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            $this->quoteSendAction(),
            $this->quoteDownloadPdfAction(),
            $this->quotePortalAction(),
            $this->quoteConvertToOrderAction(),
        ];
    }
}
