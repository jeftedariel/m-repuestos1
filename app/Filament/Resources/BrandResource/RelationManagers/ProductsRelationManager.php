<?php

namespace App\Filament\Resources\BrandResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    
    protected static ?string $modelLabel = 'Producto';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label("Nombre")
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
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
                    ->sortable()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                    ]),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoria')
                    ->sortable()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                        
                    ]),

                Tables\Columns\TextColumn::make('salePrice')
                    ->label('Precio de Venta')
                    ->money()
                    ->formatStateUsing(fn ($state) => number_format($state, 2))
                    ->prefix('₡')
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
            ])
            ->actions([
            ])
            ->bulkActions([
            ]);
    }
}