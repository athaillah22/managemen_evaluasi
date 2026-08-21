<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Karyawan';

    protected static ?string $label = 'Karyawan';

    protected static ?string $pluralLabel = 'Karyawan';

    protected static ?string $navigationGroup = 'Master Data';

    // Hanya HR yang boleh mengakses
    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'hr';
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->role === 'hr';
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->role === 'hr';
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->role === 'hr';
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->role === 'hr';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Data Karyawan')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation) => $operation === 'create')
                            ->dehydrated(fn (?string $state) => filled($state))
                            ->maxLength(255),

                        Forms\Components\Select::make('role')
                            ->label('Role')
                            ->options([
                                'employee' => 'Employee',
                                'manager' => 'Manager',
                                'hr' => 'HR',
                            ])
                            ->default('employee')
                            ->required()
                            ->native(false),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),

                        Forms\Components\Select::make('manager_id')
                            ->label('Atasan (Manager)')
                            ->relationship(
                                name: 'manager',
                                modifyQueryUsing: fn (Builder $query) => $query->whereIn('role', ['manager', 'hr']),
                            )
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->native(false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'hr' => 'HR',
                        'manager' => 'Manager',
                        default => 'Employee',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'hr' => 'success',
                        'manager' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('manager.name')
                    ->label('Atasan')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Role')
                    ->options([
                        'hr' => 'HR',
                        'manager' => 'Manager',
                        'employee' => 'Employee',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
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