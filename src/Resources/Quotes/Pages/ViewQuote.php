<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Quotes\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use VentureDrake\LaravelCrmFilament\Resources\Quotes\QuoteResource;

class ViewQuote extends ViewRecord
{
    use Concerns\HasQuoteAcceptAction;
    use Concerns\HasQuoteConvertToOrderAction;
    use Concerns\HasQuotePortalAction;
    use Concerns\HasQuoteRejectAction;
    use Concerns\HasQuoteSendAction;
    use Concerns\HasQuoteUnacceptAction;

    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            QuoteResource::backToIndexAction(),
            $this->quoteAcceptAction(),
            $this->quoteRejectAction(),
            ...$this->quoteUnacceptActions(),
            $this->quoteConvertToOrderAction(),
            $this->quoteSendAction(),
            $this->quotePortalAction(),
            $this->quoteDownloadPdfAction(),
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
