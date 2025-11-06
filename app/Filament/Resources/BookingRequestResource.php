<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingRequestResource\Pages;
use App\Filament\Resources\BookingRequestResource\RelationManagers;
use App\Models\BookingRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BookingRequestResource extends Resource
{
    protected static ?string $model = BookingRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Bookings';

    protected static ?string $modelLabel = 'Booking Request';

    protected static ?string $pluralModelLabel = 'Booking Requests';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('client_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('job_role_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('location')
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('shift_start_time')
                    ->required(),
                Forms\Components\DateTimePicker::make('shift_end_time')
                    ->required(),
                Forms\Components\TextInput::make('candidates_needed')
                    ->required()
                    ->numeric()
                    ->default(1),
                Forms\Components\TextInput::make('client_rate')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('worker_rate')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\Textarea::make('special_requirements')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('created_by')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('job_role_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('location')
                    ->searchable(),
                Tables\Columns\TextColumn::make('shift_start_time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('shift_end_time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('candidates_needed')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client_rate')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('worker_rate')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_by')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookingRequests::route('/'),
            'create' => Pages\CreateBookingRequest::route('/create'),
            'edit' => Pages\EditBookingRequest::route('/{record}/edit'),
        ];
    }
}
