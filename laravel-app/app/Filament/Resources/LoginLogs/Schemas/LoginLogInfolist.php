<?php

namespace App\Filament\Resources\LoginLogs\Schemas;

use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LoginLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Info Login')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('waktu')
                                ->label('Waktu')
                                ->dateTime('d M Y H:i:s'),
                            TextEntry::make('username')
                                ->label('Akun'),
                            TextEntry::make('nama_pegawai')
                                ->label('Nama Pegawai'),
                            TextEntry::make('role')
                                ->label('Role'),
                            TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->color(fn (string $state): string => $state === 'sukses' ? 'success' : 'danger')
                                ->formatStateUsing(fn (string $state): string => $state === 'sukses' ? 'Sukses' : 'Gagal'),
                            TextEntry::make('keterangan')
                                ->label('Keterangan')
                                ->placeholder('—'),
                        ]),
                    ]),
                Section::make('Lokasi Geografis')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('lokasi_text')
                                ->label('Lokasi')
                                ->placeholder('Tidak terdeteksi'),
                            TextEntry::make('latitude')
                                ->label('Latitude')
                                ->visible(fn ($record): bool => $record?->latitude !== null),
                            TextEntry::make('longitude')
                                ->label('Longitude')
                                ->visible(fn ($record): bool => $record?->longitude !== null),
                        ]),
                    ]),
                Section::make('Perangkat & Jaringan')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('browser')
                                ->label('Browser')
                                ->placeholder('—'),
                            TextEntry::make('os')
                                ->label('Sistem Operasi')
                                ->placeholder('—'),
                            TextEntry::make('device_type')
                                ->label('Jenis Perangkat')
                                ->placeholder('—'),
                            TextEntry::make('ip_address')
                                ->label('Alamat IP')
                                ->placeholder('—'),
                        ]),
                        TextEntry::make('user_agent')
                            ->label('User Agent')
                            ->copyable()
                            ->placeholder('—'),
                    ]),
            ]);
    }
}
