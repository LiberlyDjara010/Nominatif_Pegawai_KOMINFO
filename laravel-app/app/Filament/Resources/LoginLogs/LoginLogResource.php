<?php

namespace App\Filament\Resources\LoginLogs;

use App\Filament\Resources\LoginLogs\Pages\ListLoginLogs;
use App\Filament\Resources\LoginLogs\Pages\ViewLoginLog;
use App\Filament\Resources\LoginLogs\Schemas\LoginLogInfolist;
use App\Filament\Resources\LoginLogs\Tables\LoginLogsTable;
use App\Models\LoginLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LoginLogResource extends Resource
{
    protected static ?string $model = LoginLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Riwayat Login';

    protected static ?string $modelLabel = 'Riwayat Login';

    protected static ?string $pluralModelLabel = 'Riwayat Login';

    protected static ?string $slug = 'riwayat-login';

    public static function form(Schema $schema): Schema
    {
        return LoginLogInfolist::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LoginLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LoginLogsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->untukPemirsa(auth()->user());
    }

    public static function getNavigationBadge(): ?string
    {
        $jumlahBelumDibaca = static::getEloquentQuery()
            ->where('dibaca', false)
            ->count();

        return $jumlahBelumDibaca > 0 ? (string) $jumlahBelumDibaca : null;
    }

    public static function getNavigationBadgeColor(): string | array | null
    {
        return 'danger';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLoginLogs::route('/'),
            'view' => ViewLoginLog::route('/{record}'),
        ];
    }
}
