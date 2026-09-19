<?php

namespace VentureDrake\LaravelCrmFilament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Str;
use VentureDrake\LaravelCrm\Models\Lead;
use VentureDrake\LaravelCrm\Models\PipelineStage;
use VentureDrake\LaravelCrmFilament\Support\DefaultPipeline;

class LeadsByStageChart extends ChartWidget
{
    protected int | string | array $columnSpan = 1;

    public function getHeading(): ?string
    {
        return __('laravel-crm-filament::labels.dashboard.leads_by_pipeline_stage');
    }

    public static function translateStageName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        $snake = Str::snake(str_replace(['-', '_', '/'], ' ', $name));
        $key = 'laravel-crm-filament::labels.stages.' . $snake;
        $trans = __($key);
        if ($trans !== $key) {
            return $trans;
        }

        $lower = strtolower($name);
        $keyLower = 'laravel-crm-filament::labels.stages.' . $lower;
        $transLower = __($keyLower);
        if ($transLower !== $keyLower) {
            return $transLower;
        }

        // Arabic fallback translations for standard CRM stage names
        $arFallbacks = [
            'lead_in' => 'عميل محتمل وارد',
            'lead in' => 'عميل محتمل وارد',
            'contacted' => 'تم التواصل',
            'proposal_sent' => 'تم إرسال العرض',
            'proposal sent' => 'تم إرسال العرض',
            'negotiation' => 'تفاوض',
            'closed_won' => 'مغلقة بربح',
            'closed won' => 'مغلقة بربح',
            'closed_lost' => 'مغلقة بخسارة',
            'closed lost' => 'مغلقة بخسارة',
            'prospect' => 'عميل محتمل',
            'qualified' => 'مؤهل',
            'won' => 'رابحة',
            'lost' => 'خاسرة',
            'new' => 'جديد',
            'appointment_scheduled' => 'تمت جدولة موعد',
            'appointment scheduled' => 'تمت جدولة موعد',
            'qualified_to_buy' => 'مؤهل للشراء',
            'qualified to buy' => 'مؤهل للشراء',
            'presentation_scheduled' => 'تمت جدولة عرض تقديمي',
            'presentation scheduled' => 'تمت جدولة عرض تقديمي',
            'decision_maker_bought_in' => 'موافقة صاحب القرار',
            'decision maker bought-in' => 'موافقة صاحب القرار',
            'contract_sent' => 'تم إرسال العقد',
            'contract sent' => 'تم إرسال العقد',
            'pending' => 'قيد الانتظار',
            'in_progress' => 'قيد التنفيذ',
            'in progress' => 'قيد التنفيذ',
            'complete' => 'مكتمل',
            'cancelled' => 'ملغى',
            'draft' => 'مسودة',
            'sent' => 'مرسل',
            'accepted' => 'مقبول',
            'declined' => 'مرفوض',
            'paid' => 'مدفوع',
            'overdue' => 'متأخرة',
            'in_transit' => 'قيد النقل',
            'in transit' => 'قيد النقل',
            'delivered' => 'تم التوصيل',
            'received' => 'تم الاستلام',
        ];

        if (app()->getLocale() === 'ar' && (isset($arFallbacks[$snake]) || isset($arFallbacks[$lower]))) {
            return $arFallbacks[$snake] ?? $arFallbacks[$lower];
        }

        return __($name);
    }

    protected function getData(): array
    {
        $pipeline = DefaultPipeline::ensureFor(Lead::class);

        $stages = PipelineStage::query()
            ->where('pipeline_id', $pipeline->id)
            ->orderBy('order')
            ->get();

        $stageIds = $stages->pluck('id')->all();
        $defaultStageId = $stages->first()?->id;

        // Build name-to-id mapping for the pipeline's stages
        $stageNameToId = [];
        foreach ($stages as $s) {
            $stageNameToId[strtolower(trim($s->name))] = $s->id;
            $stageNameToId[Str::snake(str_replace(['-', '_', '/'], ' ', trim($s->name)))] = $s->id;
        }

        $leads = Lead::query()
            ->whereNull('converted_at')
            ->get(['id', 'pipeline_stage_id', 'pipeline_id']);

        // Check for leads assigned to stages from other pipelines (e.g. global default before tenant pipeline creation)
        $externalStageIds = $leads->pluck('pipeline_stage_id')->filter()->diff($stageIds)->unique()->values();
        $externalStages = $externalStageIds->isNotEmpty()
            ? PipelineStage::withoutGlobalScopes()->whereIn('id', $externalStageIds)->pluck('name', 'id')->all()
            : [];

        $counts = [];
        foreach ($leads as $lead) {
            $stageId = $lead->pipeline_stage_id;

            if ($stageId && in_array($stageId, $stageIds)) {
                $matchedId = $stageId;
            } elseif ($stageId && isset($externalStages[$stageId])) {
                $extName = strtolower(trim($externalStages[$stageId]));
                $extSnake = Str::snake(str_replace(['-', '_', '/'], ' ', $extName));
                $matchedId = $stageNameToId[$extName] ?? $stageNameToId[$extSnake] ?? $defaultStageId;
            } else {
                $matchedId = $defaultStageId;
            }

            if ($matchedId) {
                $counts[$matchedId] = ($counts[$matchedId] ?? 0) + 1;
            }
        }

        $labels = $stages->map(fn ($stage) => static::translateStageName($stage->name))->all();

        return [
            'datasets' => [
                [
                    'label' => __('laravel-crm-filament::labels.dashboard.open_leads'),
                    'data' => $stages->map(fn ($s) => (int) ($counts[$s->id] ?? 0))->all(),
                    'backgroundColor' => '#05b3a9',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'suggestedMin' => 0,
                    'suggestedMax' => 5,
                    'ticks' => [
                        'precision' => 0,
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}
