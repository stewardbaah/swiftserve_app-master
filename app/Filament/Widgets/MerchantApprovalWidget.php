<?php

namespace App\Filament\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class MerchantApprovalWidget extends Widget
{
    protected string $view = 'filament.widgets.merchant-approval-widget';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $tenant = Filament::getTenant();

        return $tenant && ! $tenant->is_approved;
    }
}
