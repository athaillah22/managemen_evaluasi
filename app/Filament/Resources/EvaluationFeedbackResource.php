<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EvaluationFeedbackResource\Pages;
use App\Models\ManagerEvaluation;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EvaluationFeedbackResource extends Resource
{
    protected static ?string $model = ManagerEvaluation::class;

    protected static ?string $navigationIcon = 'heroicon-m-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Feedback Karyawan';
    protected static ?string $label = 'Feedback Karyawan';
    protected static ?string $pluralLabel = 'Feedback Karyawan';
    protected static ?string $navigationGroup = 'Evaluasi';
    protected static ?int $navigationSort = 3;

    /**
     * Scoping (Bab 7):
     * - Employee : hasil evaluasi atas tugasnya sendiri
     * - Manager  : evaluasi yang ia buat (untuk membaca feedback karyawan)
     * - HR       : semua evaluasi + feedback
     */
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if ($user->isHr()) {
            return parent::getEloquentQuery();
        }

        if ($user->isManager()) {
            return parent::getEloquentQuery()->where('manager_id', $user->id);
        }

        return parent::getEloquentQuery()
            ->whereHas('task', fn ($q) => $q->where('assigned_to_user_id', $user->id));
    }

    public static function table(Table $table): Table
{
    return $table
        ->heading('Hasil Evaluasi Atasan & Feedback Karyawan')
        ->columns([
            Tables\Columns\TextColumn::make('task.title')
                ->label('Tugas')->searchable()->limit(40)->wrap(),
            Tables\Columns\TextColumn::make('task.assignedTo.name')->label('Penerima'),
            Tables\Columns\TextColumn::make('manager.name')->label('Penilai'),
            Tables\Columns\TextColumn::make('score')->label('Nilai')->badge()->color('warning'),

            // WRAP tanpa limit => isi catatan terbaca penuh, turun ke bawah
            Tables\Columns\TextColumn::make('note')
                ->label('Catatan Evaluasi')
                ->wrap(),

            Tables\Columns\TextColumn::make('employee_feedback')
                ->label('Feedback untuk Atasan')
                ->wrap()
                ->placeholder('Belum ada feedback'),

            Tables\Columns\TextColumn::make('employee_feedback_at')
                ->label('Dikirim')->dateTime('d M Y H:i')->placeholder('-'),
        ])
        ->actions([
            /* ===== BARU: modal detail, baca penuh & rapi ===== */
            Tables\Actions\Action::make('detail')
                ->label('Lihat Detail')->icon('heroicon-m-eye')
                ->modalHeading(fn (ManagerEvaluation $record) => 'Evaluasi: ' . ($record->task?->title ?? '-'))
                ->modalContent(fn (ManagerEvaluation $record) => view(
                    'filament.evaluations.feedback-detail',
                    ['record' => $record->load(['task.assignedTo', 'manager'])]
                ))
                ->modalWidth('lg')
                ->modalSubmitActionLabel('Tutup')
                ->action(fn () => null),

            /* Employee memberi feedback (sekali) */
            Tables\Actions\Action::make('giveFeedback')
                ->label('Beri Feedback')->icon('heroicon-m-chat-bubble-bottom-center')->color('warning')
                ->visible(fn (ManagerEvaluation $record): bool =>
                    auth()->id() === $record->task?->assigned_to_user_id
                    && blank($record->employee_feedback))
                ->modalHeading('Feedback untuk Atasan tentang Evaluasi Ini')
                ->modalDescription(fn (ManagerEvaluation $record) =>
                    'Penilai: ' . ($record->manager?->name ?? '-') . ' · Nilai: ' . $record->score . '/5')
                ->form([
                    Forms\Components\Textarea::make('employee_feedback')
                        ->label('Tanggapan / Feedback Anda kepada Atasan')
                        ->rows(4)->required()->maxLength(1000),
                ])
                ->action(function (ManagerEvaluation $record, array $data): void {
                    abort_unless(auth()->id() === $record->task?->assigned_to_user_id, 403);

                    $record->update([
                        'employee_feedback'    => $data['employee_feedback'],
                        'employee_feedback_at' => now(),
                    ]);
                }),

            /* Employee merevisi feedback */
            Tables\Actions\Action::make('editFeedback')
                ->label('Edit Feedback')->icon('heroicon-m-pencil-square')
                ->visible(fn (ManagerEvaluation $record): bool =>
                    auth()->id() === $record->task?->assigned_to_user_id
                    && filled($record->employee_feedback))
                ->form([
                    Forms\Components\Textarea::make('employee_feedback')
                        ->label('Tanggapan / Feedback Anda kepada Atasan')
                        ->rows(4)->required()->maxLength(1000)
                        ->default(fn (ManagerEvaluation $record) => $record->employee_feedback),
                ])
                ->action(function (ManagerEvaluation $record, array $data): void {
                    abort_unless(auth()->id() === $record->task?->assigned_to_user_id, 403);

                    $record->update([
                        'employee_feedback'    => $data['employee_feedback'],
                        'employee_feedback_at' => now(),
                    ]);
                }),
        ])
        ->defaultSort('created_at', 'desc');
}

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvaluationFeedbacks::route('/'),
        ];
    }
}