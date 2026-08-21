<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ManagerEvaluationResource\Pages;
use App\Models\ManagerEvaluation;
use App\Models\Task;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ManagerEvaluationResource extends Resource
{
    protected static ?string $model = ManagerEvaluation::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'Evaluasi Atasan';

    protected static ?string $label = 'Evaluasi Atasan';

    protected static ?string $pluralLabel = 'Evaluasi Atasan';

    protected static ?string $navigationGroup = 'Evaluasi';

    // Hanya HR & Manager yang bisa akses (Bab 7)
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user !== null && ($user->isHr() || $user->isManager());
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        return $user !== null && ($user->isHr() || $user->isManager());
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery()->with(['task.assignedTo', 'manager']);

        // HR lihat semua evaluasi, Manager hanya yang ia buat
        if ($user !== null && ! $user->isHr()) {
            $query->where('manager_id', $user->id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('task_id')
                    ->label('Tugas yang Dinilai')
                    ->options(function (): array {
                        $user = auth()->user();

                        $query = Task::query()
                            // Masalah 2: hanya tugas berstatus Done
                            ->where('status', 'completed')
                            // HR = semua tugas (termugas milik Manager); Manager = hanya bawahannya
                            ->when(! $user->isHr(), fn (Builder $q) => $q->whereHas(
                                'assignedTo', fn (Builder $s) => $s->where('manager_id', $user->id)
                            ))
                            // Masalah 3: anti duplikat per (tugas, penilai)
                            ->whereDoesntHave('managerEvaluations', fn (Builder $q) => $q->where('manager_id', $user->id))
                            ->with('assignedTo');

                        return $query->get()
                            ->mapWithKeys(fn (Task $task) => [
                                $task->id => $task->title
                                    . ' — ' . ($task->assignedTo?->name ?? 'Tanpa penerima')
                                    . ' (' . ucfirst($task->assignedTo?->role ?? '-') . ')',
                            ])
                            ->all();
                    })
                    ->required()
                    ->searchable()
                    ->native(false)
                    ->disabledOn('edit')
                    ->helperText('HR: semua tugas Done (termasuk milik Manager). Manager: hanya tugas Done bawahannya.'),

                Forms\Components\Select::make('score')
                    ->label('Nilai (1-5)')
                    ->options([
                        1 => '1 - Sangat Buruk',
                        2 => '2 - Buruk',
                        3 => '3 - Cukup',
                        4 => '4 - Baik',
                        5 => '5 - Sangat Baik',
                    ])
                    ->required()
                    ->native(false),

                Forms\Components\Textarea::make('note')
                    ->label('Catatan Evaluasi')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Validasi backend: kunci manager_id = user login & pastikan tugas Done + validasi scope.
     * Mencegah manipulasi form/URL (Masalah 5).
     */
    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        $task = Task::find($data['task_id'] ?? null);

        abort_unless($task !== null && $task->status === 'completed', 422, 'Tugas belum berstatus Done.');
        abort_unless(
            $user->isHr() || $task->assignedTo?->manager_id === $user->id,
            403,
            'Manager hanya dapat menilai tugas bawahannya sendiri.'
        );

        $data['manager_id'] = $user->id;

        return $data;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('task.title')
                    ->label('Tugas')
                    ->searchable()
                    ->limit(40)
                    ->wrap(),

                Tables\Columns\TextColumn::make('task.assignedTo.name')
                    ->label('Karyawan')
                    ->sortable()
                    ->description(fn (ManagerEvaluation $record): string => ucfirst($record->task?->assignedTo?->role ?? '-')),

                Tables\Columns\TextColumn::make('score')
                    ->label('Nilai')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 4 => 'success',
                        $state === 3 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('manager.name')
                    ->label('Dievaluasi Oleh')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListManagerEvaluations::route('/'),
            'create' => Pages\CreateManagerEvaluation::route('/create'),
        ];
    }
}