<?php

namespace App\Filament\Resources\ProductResource\Widgets;

use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductOverview extends BaseWidget
{   
    public ?Product $record = null;

    protected function getStats(): array
    {
        return [

            Stat::make('Margen de Ganancia', number_format($this->record->getProfitMargin()) . "%")
                ->icon('heroicon-s-percent-badge') 
                ->description('Porcentaje')
                ->descriptionColor('success'),

            Stat::make('Ganancia', '₡' . number_format($this->record->getProfit(), 2))
                ->icon('heroicon-s-percent-badge') 
                ->description('Neta después de IVA')
                ->descriptionColor('success'),
        ];
        
    }
}
