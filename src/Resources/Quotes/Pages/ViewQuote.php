<?php

namespace VentureDrake\LaravelCrmFilament\Resources\Quotes\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
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
            $this->quoteSendAction()
                ->button()
                ->label(__('laravel-crm-filament::labels.actions.send')),
            $this->quoteAcceptAction(),
            $this->quoteRejectAction(),
            ...$this->quoteUnacceptActions(),
            $this->quoteConvertToOrderAction(),
            $this->quotePortalAction()
                ->button()
                ->hiddenLabel()
                ->icon('heroicon-m-arrow-top-right-on-square'),
            $this->quoteDownloadPdfAction()
                ->hiddenLabel(),
            Actions\EditAction::make()
                ->button()
                ->hiddenLabel()
                ->icon('heroicon-m-pencil-square'),
            Actions\DeleteAction::make()
                ->button()
                ->hiddenLabel()
                ->icon('heroicon-m-trash'),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 2])->schema([
                $this->getInfolistContentComponent()->columnSpan(['lg' => 1]),
                $this->getRelationManagersContentComponent()->columnSpan(['lg' => 1]),
            ]),
        ]);
    }

    public function getSubheading(): string | Htmlable | null
    {
        $stage = $this->record?->pipelineStage?->name;

        if (! $stage) {
            return null;
        }

        return new HtmlString(
            '<span class="inline-flex items-center rounded-md bg-gray-900 px-3 py-1 text-sm font-medium text-white dark:bg-gray-100 dark:text-gray-900">'
            . e($stage)
            . '</span>'
        );
    }
}
