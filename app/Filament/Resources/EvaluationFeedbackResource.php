<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EvaluationFeedbackResource\Pages;
use App\Models\ManagerEvaluation;
use App\Models\ReceiverEvaluation;
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
     * Scoping:
     * - Employee : hasil evaluasi atas tugasnya sendiri
     * - Manager  : evaluasi yang ia buat
     * - HR      : semua evaluasi
     */
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if ($user->isHr()) {
            return parent::getEloquentQuery();
        }

        if ($user->isManager()) {
            return parent::getEloquentQuery()
                ->where('manager_id', $user->id);
        }

        return parent::getEloquentQuery()
            ->whereHas('task', function ($q) use ($user) {
                $q->where('assigned_to_user_id', $user->id);
            });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Hasil Evaluasi Atasan & Feedback Karyawan')

            ->columns([
                Tables\Columns\TextColumn::make('task.title')
                    ->label('Tugas')
                    ->searchable()
                    ->limit(40)
                    ->wrap(),

                Tables\Columns\TextColumn::make('task.assignedTo.name')
                    ->label('Penerima'),

                Tables\Columns\TextColumn::make('manager.name')
                    ->label('Penilai'),

                Tables\Columns\TextColumn::make('score')
                    ->label('Nilai')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('note')
                    ->label('Catatan Evaluasi')
                    ->wrap(),

                /*
                 * Feedback karyawan diambil dari
                 * receiver_evaluations.note
                 */
                Tables\Columns\TextColumn::make('employee_feedback')
                    ->label('Feedback untuk Atasan')
                    ->wrap()
                    ->placeholder('Belum ada feedback')
                    ->state(function (ManagerEvaluation $record) {
                        $receiverId = $record->task?->assigned_to_user_id;

                        if (!$receiverId) {
                            return null;
                        }

                        return ReceiverEvaluation::query()
                            ->where('task_id', $record->task_id)
                            ->where('receiver_id', $receiverId)
                            ->value('note');
                    }),

                /*
                 * Waktu feedback diambil dari
                 * receiver_evaluations.updated_at
                 */
                Tables\Columns\TextColumn::make('employee_feedback_at')
                    ->label('Dikirim')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->state(function (ManagerEvaluation $record) {
                        $receiverId = $record->task?->assigned_to_user_id;

                        if (!$receiverId) {
                            return null;
                        }

                        return ReceiverEvaluation::query()
                            ->where('task_id', $record->task_id)
                            ->where('receiver_id', $receiverId)
                            ->value('updated_at');
                    }),
            ])

            ->actions([

                /*
                 * ==============================
                 * DETAIL
                 * ==============================
                 */
                Tables\Actions\Action::make('detail')
                    ->label('Lihat Detail')
                    ->icon('heroicon-m-eye')

                    ->modalHeading(
                        fn (ManagerEvaluation $record) =>
                            'Evaluasi: ' . ($record->task?->title ?? '-')
                    )

                    ->modalContent(
                        fn (ManagerEvaluation $record) =>
                            view(
                                'filament.evaluations.feedback-detail',
                                [
                                    'record' => $record->load([
                                        'task.assignedTo',
                                        'manager',
                                    ]),
                                ]
                            )
                    )

                    ->modalWidth('lg')
                    ->modalSubmitActionLabel('Tutup')
                    ->action(fn () => null),

                /*
                 * ==============================
                 * BERI FEEDBACK
                 * ==============================
                 */
                Tables\Actions\Action::make('giveFeedback')
                    ->label('Beri Feedback')
                    ->icon('heroicon-m-chat-bubble-bottom-center')
                    ->color('warning')

                    ->visible(function (ManagerEvaluation $record): bool {
                        $receiverId = $record->task?->assigned_to_user_id;

                        if (!$receiverId) {
                            return false;
                        }

                        $existingFeedback = ReceiverEvaluation::query()
                            ->where('task_id', $record->task_id)
                            ->where('receiver_id', $receiverId)
                            ->exists();

                        return auth()->id() === $receiverId
                            && !$existingFeedback;
                    })

                    ->modalHeading('Feedback untuk Atasan tentang Evaluasi Ini')

                    ->modalDescription(
                        fn (ManagerEvaluation $record) =>
                            'Penilai: ' .
                            ($record->manager?->name ?? '-') .
                            ' · Nilai: ' .
                            $record->score .
                            '/5'
                    )

                    ->form([

                        /*
                         * Nilai kejelasan instruksi
                         */
                        Forms\Components\Select::make('clarity_score')
                            ->label('Kejelasan Instruksi')
                            ->options([
                                1 => '1 - Sangat Tidak Jelas',
                                2 => '2 - Tidak Jelas',
                                3 => '3 - Cukup Jelas',
                                4 => '4 - Jelas',
                                5 => '5 - Sangat Jelas',
                            ])
                            ->required(),

                        /*
                         * Nilai tingkat kesulitan
                         */
                        Forms\Components\Select::make('difficulty_score')
                            ->label('Tingkat Kesulitan')
                            ->options([
                                1 => '1 - Sangat Mudah',
                                2 => '2 - Mudah',
                                3 => '3 - Sedang',
                                4 => '4 - Sulit',
                                5 => '5 - Sangat Sulit',
                            ])
                            ->required(),

                        /*
                         * Catatan / feedback
                         */
                        Forms\Components\Textarea::make('employee_feedback')
                            ->label('Tanggapan / Feedback Anda kepada Atasan')
                            ->rows(4)
                            ->required()
                            ->maxLength(1000),
                    ])

                    ->action(
                        function (
                            ManagerEvaluation $record,
                            array $data
                        ): void {

                            $receiverId = $record->task?->assigned_to_user_id;

                            abort_unless(
                                auth()->id() === $receiverId,
                                403
                            );

                            /*
                             * Simpan ke receiver_evaluations.
                             *
                             * TIDAK disimpan lagi ke:
                             * manager_evaluations.employee_feedback
                             */
                            ReceiverEvaluation::updateOrCreate(
                                [
                                    'task_id' => $record->task_id,
                                    'receiver_id' => $receiverId,
                                ],
                                [
                                    'clarity_score' => $data['clarity_score'],
                                    'difficulty_score' => $data['difficulty_score'],
                                    'note' => $data['employee_feedback'],
                                ]
                            );
                        }
                    ),

                /*
                 * ==============================
                 * EDIT FEEDBACK
                 * ==============================
                 */
                Tables\Actions\Action::make('editFeedback')
                    ->label('Edit Feedback')
                    ->icon('heroicon-m-pencil-square')

                    ->visible(function (ManagerEvaluation $record): bool {
                        $receiverId = $record->task?->assigned_to_user_id;

                        if (!$receiverId) {
                            return false;
                        }

                        $existingFeedback = ReceiverEvaluation::query()
                            ->where('task_id', $record->task_id)
                            ->where('receiver_id', $receiverId)
                            ->exists();

                        return auth()->id() === $receiverId
                            && $existingFeedback;
                    })

                    ->modalHeading('Edit Feedback untuk Atasan')

                    ->form([

                        /*
                         * Kejelasan Instruksi
                         */
                        Forms\Components\Select::make('clarity_score')
                            ->label('Kejelasan Instruksi')
                            ->options([
                                1 => '1 - Sangat Tidak Jelas',
                                2 => '2 - Tidak Jelas',
                                3 => '3 - Cukup Jelas',
                                4 => '4 - Jelas',
                                5 => '5 - Sangat Jelas',
                            ])
                            ->required()
                            ->default(function (ManagerEvaluation $record) {
                                $receiverId = $record->task?->assigned_to_user_id;

                                if (!$receiverId) {
                                    return null;
                                }

                                return ReceiverEvaluation::query()
                                    ->where('task_id', $record->task_id)
                                    ->where('receiver_id', $receiverId)
                                    ->value('clarity_score');
                            }),

                        /*
                         * Tingkat Kesulitan
                         */
                        Forms\Components\Select::make('difficulty_score')
                            ->label('Tingkat Kesulitan')
                            ->options([
                                1 => '1 - Sangat Mudah',
                                2 => '2 - Mudah',
                                3 => '3 - Sedang',
                                4 => '4 - Sulit',
                                5 => '5 - Sangat Sulit',
                            ])
                            ->required()
                            ->default(function (ManagerEvaluation $record) {
                                $receiverId = $record->task?->assigned_to_user_id;

                                if (!$receiverId) {
                                    return null;
                                }

                                return ReceiverEvaluation::query()
                                    ->where('task_id', $record->task_id)
                                    ->where('receiver_id', $receiverId)
                                    ->value('difficulty_score');
                            }),

                        /*
                         * Feedback
                         */
                        Forms\Components\Textarea::make('employee_feedback')
                            ->label('Tanggapan / Feedback Anda kepada Atasan')
                            ->rows(4)
                            ->required()
                            ->maxLength(1000)
                            ->default(function (ManagerEvaluation $record) {
                                $receiverId = $record->task?->assigned_to_user_id;

                                if (!$receiverId) {
                                    return null;
                                }

                                return ReceiverEvaluation::query()
                                    ->where('task_id', $record->task_id)
                                    ->where('receiver_id', $receiverId)
                                    ->value('note');
                            }),
                    ])

                    ->action(
                        function (
                            ManagerEvaluation $record,
                            array $data
                        ): void {

                            $receiverId = $record->task?->assigned_to_user_id;

                            abort_unless(
                                auth()->id() === $receiverId,
                                403
                            );

                            ReceiverEvaluation::updateOrCreate(
                                [
                                    'task_id' => $record->task_id,
                                    'receiver_id' => $receiverId,
                                ],
                                [
                                    'clarity_score' => $data['clarity_score'],
                                    'difficulty_score' => $data['difficulty_score'],
                                    'note' => $data['employee_feedback'],
                                ]
                            );
                        }
                    ),
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