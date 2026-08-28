<?php

namespace App\Filament\Resources\LoginLogs\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use App\Models\LoginLog;

class LoginLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('waktu', 'desc')
            ->columns([
                TextColumn::make('waktu')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),
                TextColumn::make('username')
                    ->label('Akun')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nama_pegawai')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'sukses' ? 'success' : 'danger')
                    ->formatStateUsing(fn (string $state): string => $state === 'sukses' ? 'Sukses' : 'Gagal'),
                TextColumn::make('lokasi_text')
                    ->label('Lokasi')
                    ->placeholder('—'),
                TextColumn::make('ip_address')
                    ->label('IP'),
                TextColumn::make('browser')
                    ->label('Perangkat')
                    ->formatStateUsing(fn ($record): string => trim($record->browser . ' · ' . $record->device_type, ' ·')),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'sukses' => 'Sukses',
                        'gagal' => 'Gagal',
                    ]),
                TernaryFilter::make('dibaca')
                    ->label('Dibaca'),
            ], layout: FiltersLayout::AboveContent)
            ->recordActions([
                ViewAction::make(),
                Action::make('tandaiDibaca')
                    ->label('Tandai dibaca')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (LoginLog $record): bool => ! $record->dibaca)
                    ->requiresConfirmation()
                    ->action(function (LoginLog $record): void {
                        $record->update(['dibaca' => true]);
                    }),
            ])
            ->searchable();
    }
}
