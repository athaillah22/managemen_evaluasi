<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReceiverEvaluationResource\Pages;
use App\Models\ReceiverEvaluation;
use App\Models\Task;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReceiverEvaluationResource extends Resource
{
    protected static ?string $model = ReceiverEvaluation::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left';

    protected static ?string $navigationLabel = 'Feedback Karyawan';

    protected static ?string $label = 'Feedback Karyawan';

    protected static ?string $pluralLabel = 'Feedback Karyawan';

    protected static ?string $navigationGroup = 'Evaluasi';

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery()->with(['task', 'receiver']);

        // HR lihat semua, lainnya hanya feedback miliknya
        if ($user?->role !== 'hr') {
            $query->where('receiver_id', $user?->id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('task_id')
                    ->label('Tugas')
                    ->options(function (): array {
                        $user = auth()->user();

                        return Task::query()
                            ->where('assigned_to_user_id', $user->id)
                            ->whereDoesntHave('receiverEvaluation', fn (Builder $q) => $q->where('receiver_id', $user->id))
                            ->pluck('title', 'id')
                            ->all();
                    })
                    ->required()
                    ->searchable()
                    ->native(false)
                    ->disabledOn('edit'),

                Forms\Components\Select::make('clarity_score')
                    ->label('Kejelasan Instruksi (1-5)')
                    ->options([
                        1 => '1 - Sangat Tidak Jelas',
                        2 => '2 - Tidak Jelas',
                        3 => '3 - Cukup Jelas',
                        4 => '4 - Jelas',
                        5 => '5 - Sangat Jelas',
                    ])
                    ->required()
                    ->native(false),

                Forms\Components\Select::make('difficulty_score')
                    ->label('Tingkat Kesulitan (1-5)')
                    ->options([
                        1 => '1 - Sangat Mudah',
                        2 => '2 - Mudah',
                        3 => '3 - Sedang',
                        4 => '4 - Sulit',
                        5 => '5 - Sangat Sulit',
                    ])
                    ->required()
                    ->native(false),

                Forms\Components\Textarea::make('note')
                    ->label('Catatan')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('task.title')
                    ->label('Tugas')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('receiver.name')
                    ->label('Karyawan')
                    ->sortable(),

                Tables\Columns\TextColumn::make('clarity_score')
                    ->label('Kejelasan')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 4 => 'success',
                        $state === 3 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('difficulty_score')
                    ->label('Kesulitan')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 2 => 'success',
                        $state === 3 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReceiverEvaluations::route('/'),
            'create' => Pages\CreateReceiverEvaluation::route('/create'),
        ];
    }
}