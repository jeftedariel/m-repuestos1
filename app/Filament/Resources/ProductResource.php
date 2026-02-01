<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\Exports\Models\Export;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action as Actions;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Set;
use Filament\Notifications\Notification;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Inventario';

    protected static ?string $navigationLabel = 'Productos';

    protected static ?string $modelLabel = 'Producto';

    public static function calculateProfitMargin($state, Forms\Set $set, $get): void
    {
        $purchasePrice = (float) preg_replace('/[^0-9.]/', '', $get('purchasePrice'));
        $salePrice = (float) preg_replace('/[^0-9.]/', '', $get('salePrice'));

        $salePrice = $salePrice / 1.13;

        if ($salePrice && $purchasePrice) {
            $profit = (($salePrice - $purchasePrice) / $purchasePrice) * 100;

            $set('profitMargin', number_format($profit, 2));
        }
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('brand_id')
                    ->label("Marca")
                    ->relationship('brand', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()

                    ]),

                Forms\Components\Select::make('category_id')
                    ->label("Categoria")
                    ->relationship('category', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required(),
                        Forms\Components\TextInput::make('description')
                            ->label('Descripción'),

                    ]),

                Forms\Components\TextInput::make('code')
                    ->label("Codigo/Nombre")
                    ->required()
                    ->maxLength(20)
                    ->unique(ignoreRecord: true),

                Forms\Components\TextInput::make('description')
                    ->label("Descripción")
                    ->required()
                    ->maxLength(255),

                /*Forms\Components\FileUpload::make('image')
                    ->label("Imagen")
                    ->image()
                    ->disk('public')
                    ->visibility('public')
                    ->directory('products')
                    ->default('products/default.png')
                    ->required(),
                */
                Forms\Components\TextInput::make('purchasePrice')
                    ->label("Costo")
                    ->required()
                    ->prefix('₡')
                    ->mask(RawJs::make('$money($input)'))
                    ->live(onBlur: true)
                    ->dehydrateStateUsing(fn($state) => preg_replace('/[^0-9.]/', '', $state)) // Remove mask before saving
                    ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
                        self::calculateProfitMargin($state, $set, $get);
                    })
                    ->hintAction(
                        Actions::make('Calcular Costo')
                            ->icon('heroicon-m-arrow-left-circle')
                            ->requiresConfirmation()
                            ->action(function ($state, Set $set, $get) {
                                $salePrice = (float) preg_replace('/[^0-9.]/', '', $get('salePrice'));
                                $profitMarginPercentage = 0.40; // 40% profit

                                if ($salePrice) {
                                    $salePriceWithoutTax = $salePrice / 1.13; // Remove 13% tax
                                    $purchasePrice = $salePriceWithoutTax / (1 + $profitMarginPercentage);
                                    $set('purchasePrice', number_format($purchasePrice, 2));
                                }

                                self::calculateProfitMargin($state, $set, $get);
                            })
                    ),

                Forms\Components\TextInput::make('salePrice')
                    ->label("Precio de Venta")
                    ->required()
                    ->prefix('₡')
                    ->mask(RawJs::make('$money($input)'))
                    ->live(onBlur: true)
                    ->dehydrateStateUsing(fn($state) => preg_replace('/[^0-9.]/', '', $state)) 
                    ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
                        self::calculateProfitMargin($state, $set, $get);
                    })
                    ->hintAction(
                        Actions::make('Obtener Precio')
                            ->icon('heroicon-m-arrow-right-circle')
                            ->requiresConfirmation()
                            ->action(function ($state, Set $set, $get) {
                                $purchasePrice = (float) preg_replace('/[^0-9.]/', '', $get('purchasePrice'));
                                $profitMargin = $purchasePrice * 0.40; 

                                $salePrice = $purchasePrice + $profitMargin;
                                $salePrice = $salePrice * 1.13;
                                
                                $set('salePrice', number_format($salePrice));


                                self::calculateProfitMargin($state, $set, $get);
                            })
                    )
                    ->suffix('IVA Incluido'),


                Forms\Components\Hidden::make('profitMargin')
                    ->label("Margen de Ganancia"),

                Forms\Components\TextInput::make('quantity')
                    ->label("Cantidad")
                    ->required()
                    ->numeric()
                    ->default(0),

                Forms\Components\Toggle::make('active')
                    ->label("Estado")
                    ->required()
                    ->default(true),
            ]);
    }



    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                /*
                Tables\Columns\ImageColumn::make('image')
                    ->label('Imagen')
                    ->circular(),
                */
                Tables\Columns\TextColumn::make('code')
                    ->label('Codigo/Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('brand.name')
                    ->label('Marca')
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoria')
                    ->sortable(),

                Tables\Columns\TextColumn::make('salePrice')
                    ->label('Precio de Venta')
                    ->formatStateUsing(fn($state) => number_format($state, 2))
                    ->prefix('₡')
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('active')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('brand')
                    ->label('Marca')
                    ->relationship('brand', 'name'),

                Tables\Filters\SelectFilter::make('category')
                    ->label('Categoria')
                    ->relationship('category', 'name'),

                Tables\Filters\TernaryFilter::make('active')
                    ->label('Estado'),
            ])
            ->actions([
                Tables\Actions\Action::make('markAsOut')
                    ->label('Marcar Salida')
                    ->icon('heroicon-o-minus-circle')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\TextInput::make('quantity')
                            ->label('Cantidad a Retirar')
                            ->required()
                            ->numeric()
                            ->minValue(1),
                    ])
                    ->action(function (array $data, $record) {
                        $quantityToRemove = $data['quantity'];

                        if ($record->quantity <= 0) {
                            Notification::make()
                                ->title('Error')
                                ->body('No hay suficiente cantidad disponible para retirar.')
                                ->danger()
                                ->send();
                            return;
                        }

                        if ($quantityToRemove > $record->quantity) {
                            Notification::make()
                                ->title('Error')
                                ->body('La cantidad a retirar no puede ser mayor que la disponible.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $record->quantity -= $quantityToRemove;
                        $record->save();

                        Notification::make()
                            ->title('Éxito')
                            ->body('Cantidad retirada exitosamente.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),

            ])
            ->bulkActions([
                ExportBulkAction::make()
                    ->exports([
                        \pxlrbt\FilamentExcel\Exports\ExcelExport::make()
                            ->fromTable()
                            ->withFilename('reporte_productos_' . date('Y-m-d'))
                            ->fromTable()->except([
                                'created_at',
                                'updated_at',
                                'deleted_at',
                                'image'
                            ]),
                    ]),
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),

            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Add relation managers if needed
        ];
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
