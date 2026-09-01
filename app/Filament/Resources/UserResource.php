<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Karyawan';
    protected static ?string $label = 'Karyawan';
    protected static ?string $pluralLabel = 'Karyawan';
    protected static ?string $navigationGroup = 'Master Data';

    /* ===== Hak akses ketat: hanya HR (Bab 7) ===== */
    public static function canViewAny(): bool
    {
        return auth()->user()?->isHr() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isHr() ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->isHr() ?? false;
    }

    public static function canDelete($record): bool
    {
        // HR tidak boleh menghapus dirinya sendiri
        return (auth()->user()?->isHr() ?? false) && auth()->id() !== $record->id;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->isHr() ?? false;
    }

    /* ===== Urutan tampilan: HR → Manager → Employee, lalu alfabetis ===== */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->orderBy('name');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Data Karyawan')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama')->required()->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')->email()->required()
                            ->unique(ignoreRecord: true)->maxLength(255),

                        Forms\Components\TextInput::make('password')
                            ->label('Password')->password()->revealable()
                            ->required(fn (string $operation) => $operation === 'create')
                            ->dehydrated(fn (?string $state) => filled($state))
                            ->dehydrateStateUsing(fn (string $state) => Hash::make($state))
                            ->helperText('Kosongkan bila tidak ingin mengubah password saat edit.')
                            ->maxLength(255),

                        Forms\Components\Select::make('role')
                            ->label('Role')
                            ->options([
                                'hr' => 'HR',
                                'manager' => 'Manager',
                                'employee' => 'Employee',
                            ])
                            ->default('employee')->required()->native(false),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Akun Aktif')->default(true)
                            ->helperText('Nonaktif = tidak bisa login.'),

                        // Atasan: hanya Manager/HR, tidak boleh menunjuk diri sendiri
                        Forms\Components\Select::make('manager_id')
                            ->label('Atasan Langsung')
                            ->options(function (?User $record): array {
                                return User::query()
                                    ->whereIn('role', ['manager', 'hr'])
                                    ->where('is_active', true)
                                    ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()->preload()->nullable()->native(false)
                            ->helperText('Atasan yang berhak mengevaluasi tugas karyawan ini. Kosongkan untuk manajer puncak / HR.'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')->searchable()->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')->searchable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Role')->badge()
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
                    ->label('Aktif')->boolean(),

                Tables\Columns\TextColumn::make('manager.name')
                    ->label('Atasan')->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Role')
                    ->options(['hr' => 'HR', 'manager' => 'Manager', 'employee' => 'Employee']),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Akun')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (User $record) => auth()->id() !== $record->id)
                    ->after(function () {
                        Notification::make()->title('User berhasil dihapus')->success()->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}