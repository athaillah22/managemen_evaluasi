<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Pages;
use App\Models\Task;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Tugas';

    protected static ?string $label = 'Tugas';

    protected static ?string $pluralLabel = 'Tugas';

    protected static ?string $navigationGroup = 'Operasional';

    // Scoping daftar tugas sesuai role (Bab 7 dokumen Anda)
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery()->with(['assignedTo', 'creator']);

        return match ($user?->role) {
            'hr' => $query,

            'manager' => $query->where(function (Builder $q) use ($user) {
                $q->where('assigned_to_user_id', $user->id)
                    ->orWhere('assigned_by', $user->id)
                    ->orWhereHas('assignedTo', fn (Builder $sub) => $sub->where('manager_id', $user->id));
            }),

            default => $query->where(function (Builder $q) use ($user) {
                $q->where('assigned_to_user_id', $user->id)
                    ->orWhere('assigned_by', $user->id);
            }),
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Tugas')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Judul')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Penugasan')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(Task::STATUS_OPTIONS)
                            ->default('pending')
                            ->required()
                            ->disabled(fn (?Task $record) => $record !== null
                                && auth()->user()?->role !== 'hr'
                                && auth()->user()?->id !== $record->assigned_to_user_id)
                            ->native(false),

                        Forms\Components\Select::make('priority')
                            ->label('Prioritas')
                            ->options(Task::PRIORITY_OPTIONS)
                            ->default('medium')
                            ->required()
                            ->native(false),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Tanggal Jatuh Tempo')
                            ->nullable()
                            ->native(false),

                        Forms\Components\Select::make('assigned_to_user_id')
                            ->label('Ditugaskan Kepada')
                            ->options(fn (): array => auth()->user()?->getAssignableUsers()?->all() ?? [])
                            ->searchable()
                            ->required()
                            ->native(false)
                            ->disabled(fn (?Task $record) => $record !== null
                                && ! in_array(auth()->user()?->role, ['hr', 'manager'])),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Task::STATUS_OPTIONS[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'in_progress' => 'info',
                        'waiting_review' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Prioritas')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Task::PRIORITY_OPTIONS[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'low' => 'gray',
                        'medium' => 'info',
                        'high' => 'warning',
                        'urgent' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Ditugaskan Kepada')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(Task::STATUS_OPTIONS),

                SelectFilter::make('priority')
                    ->label('Prioritas')
                    ->options(Task::PRIORITY_OPTIONS),
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
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}