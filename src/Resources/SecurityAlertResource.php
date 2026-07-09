<?php

namespace MKWebDesign\FilamentWatchdog\Resources;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use MKWebDesign\FilamentWatchdog\Models\SecurityAlert;
use MKWebDesign\FilamentWatchdog\Resources\SecurityAlertResource\Pages;
use MKWebDesign\FilamentWatchdog\Traits\ConfiguresWatchdogNavigation;

class SecurityAlertResource extends Resource
{
    use ConfiguresWatchdogNavigation;

    protected static function getNavigationVisibility(): string
    {
        return 'conditional';
    }

    protected static function getDefaultSecuritySort(): int
    {
        return 4;
    }

    protected static ?string $model = SecurityAlert::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('filament-watchdog-v5::messages.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-watchdog-v5::messages.resource.security_alert.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('filament-watchdog-v5::messages.resource.security_alert.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-watchdog-v5::messages.resource.security_alert.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('alert_type')
                    ->label(__('filament-watchdog-v5::messages.resource.security_alert.fields.alert_type'))
                    ->options([
                        'suspicious_activity' => __('filament-watchdog-v5::messages.alert_types.suspicious_activity'),
                        'brute_force_attempt' => __('filament-watchdog-v5::messages.alert_types.brute_force_attempt'),
                        'new_file_detected' => __('filament-watchdog-v5::messages.alert_types.new_file_detected'),
                        'file_modified' => __('filament-watchdog-v5::messages.alert_types.file_modified'),
                        'file_deleted' => __('filament-watchdog-v5::messages.alert_types.file_deleted'),
                        'file_quarantined' => __('filament-watchdog-v5::messages.alert_types.file_quarantined'),
                        'malware_detected' => __('filament-watchdog-v5::messages.alert_types.malware_detected'),
                        'emergency_lockdown' => __('filament-watchdog-v5::messages.alert_types.emergency_lockdown'),
                        'emergency_lockdown_deactivated' => __('filament-watchdog-v5::messages.alert_types.emergency_lockdown_deactivated'),
                    ])
                    ->required(),
                Forms\Components\TextInput::make('title')
                    ->label(__('filament-watchdog-v5::messages.resource.security_alert.fields.title'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label(__('filament-watchdog-v5::messages.resource.security_alert.fields.description'))
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Select::make('severity')
                    ->label(__('filament-watchdog-v5::messages.resource.security_alert.fields.severity'))
                    ->options([
                        'low' => __('filament-watchdog-v5::messages.risk_level.low'),
                        'medium' => __('filament-watchdog-v5::messages.risk_level.medium'),
                        'high' => __('filament-watchdog-v5::messages.risk_level.high'),
                        'critical' => __('filament-watchdog-v5::messages.risk_level.critical'),
                    ])
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label(__('filament-watchdog-v5::messages.resource.security_alert.fields.status'))
                    ->options([
                        'new' => __('filament-watchdog-v5::messages.alert_status.new'),
                        'acknowledged' => __('filament-watchdog-v5::messages.alert_status.acknowledged'),
                        'resolved' => __('filament-watchdog-v5::messages.alert_status.resolved'),
                        'false_positive' => __('filament-watchdog-v5::messages.alert_status.false_positive'),
                    ])
                    ->required(),
                Forms\Components\Textarea::make('metadata')
                    ->label(__('filament-watchdog-v5::messages.resource.security_alert.fields.metadata'))
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('alert_type')
                    ->label(__('filament-watchdog-v5::messages.resource.security_alert.fields.alert_type'))
                    ->formatStateUsing(fn (string $state): string => __('filament-watchdog-v5::messages.alert_types.'.$state))
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->label(__('filament-watchdog-v5::messages.resource.security_alert.fields.title'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('severity')
                    ->label(__('filament-watchdog-v5::messages.resource.security_alert.fields.severity'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('filament-watchdog-v5::messages.risk_level.'.$state))
                    ->color(fn (string $state): string => match ($state) {
                        'low' => 'success',
                        'medium' => 'info',
                        'high' => 'warning',
                        'critical' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('filament-watchdog-v5::messages.resource.security_alert.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('filament-watchdog-v5::messages.alert_status.'.$state))
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'warning',
                        'acknowledged' => 'info',
                        'resolved' => 'success',
                        'false_positive' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament-watchdog-v5::messages.common.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('filament-watchdog-v5::messages.common.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('severity')
                    ->label(__('filament-watchdog-v5::messages.resource.security_alert.fields.severity'))
                    ->options([
                        'low' => __('filament-watchdog-v5::messages.risk_level.low'),
                        'medium' => __('filament-watchdog-v5::messages.risk_level.medium'),
                        'high' => __('filament-watchdog-v5::messages.risk_level.high'),
                        'critical' => __('filament-watchdog-v5::messages.risk_level.critical'),
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('filament-watchdog-v5::messages.resource.security_alert.fields.status'))
                    ->options([
                        'new' => __('filament-watchdog-v5::messages.alert_status.new'),
                        'acknowledged' => __('filament-watchdog-v5::messages.alert_status.acknowledged'),
                        'resolved' => __('filament-watchdog-v5::messages.alert_status.resolved'),
                        'false_positive' => __('filament-watchdog-v5::messages.alert_status.false_positive'),
                    ]),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSecurityAlerts::route('/'),
            'create' => Pages\CreateSecurityAlert::route('/create'),
            'view' => Pages\ViewSecurityAlert::route('/{record}'),
            'edit' => Pages\EditSecurityAlert::route('/{record}/edit'),
        ];
    }
}
