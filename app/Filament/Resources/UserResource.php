<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Users';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Enter user name'),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->placeholder('Enter email address'),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn(string $operation) => $operation === 'create')
                    ->dehydrateStateUsing(fn($state) => $state ? bcrypt($state) : null)
                    ->dehydrated(fn($state) => filled($state))
                    ->placeholder('Enter password'),
                Forms\Components\Select::make('role')
                    ->options([
                        User::ROLE_SUPERADMIN => 'Super Admin',
                        User::ROLE_ADMIN => 'Admin',
                        User::ROLE_USER => 'User',
                    ])
                    ->default(User::ROLE_USER)
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
                Forms\Components\Toggle::make('is_verified')
                    ->label('Verified'),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->placeholder('+44 123 456 7890'),
                Forms\Components\TextInput::make('address')
                    ->maxLength(500)
                    ->placeholder('Street address'),
                Forms\Components\TextInput::make('city')
                    ->maxLength(100)
                    ->placeholder('City'),
                Forms\Components\Select::make('state')
                    ->options(User::getUKStates())
                    ->placeholder('Select UK state/region'),
                Forms\Components\TextInput::make('postal_code')
                    ->maxLength(20)
                    ->placeholder('Postal code'),
                Forms\Components\TextInput::make('ip_address')
                    ->ipv4()
                    ->placeholder('127.0.0.1')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\DateTimePicker::make('last_login')
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('role')
                    ->colors([
                        'danger' => User::ROLE_SUPERADMIN,
                        'warning' => User::ROLE_ADMIN,
                        'info' => User::ROLE_USER,
                    ])
                    ->formatStateUsing(fn($state) => ucfirst($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('state')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn($state) => $state ? User::getUKStates()[$state] ?? ucfirst($state) : '-'),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_login')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        User::ROLE_SUPERADMIN => 'Super Admin',
                        User::ROLE_ADMIN => 'Admin',
                        User::ROLE_USER => 'User',
                    ]),
                Tables\Filters\SelectFilter::make('state')
                    ->options(User::getUKStates()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('Verified Status'),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
