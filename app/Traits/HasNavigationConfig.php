<?php

namespace App\Traits;

trait HasNavigationConfig
{
    public static function getNavigationGroup(): ?string
    {
        $resourceClass = static::class;
        $navigationConfig = config('filament_navigation.groups');

        foreach ($navigationConfig as $group => $settings) {
            if (in_array($resourceClass, $settings['resources'])) {
                return $group;
            }
        }

        return null;
    }

    public static function getNavigationIcon(): ?string
    {
        $resourceClass = static::class;

        // Prioridad: 1. Icono específico del recurso, 2. Icono del grupo
        if (isset(static::$navigationIcon)) {
            return static::$navigationIcon;
        }

        $navigationConfig = config('filament_navigation.groups');

        foreach ($navigationConfig as $group => $settings) {
            if (in_array($resourceClass, $settings['resources']) && isset($settings['icon'])) {
                return $settings['icon'];
            }
        }

        return null; // Icono por defecto si no hay ninguno especificado
    }

    public static function getNavigationSort(): ?int
    {
        $resourceClass = static::class;
        $navigationConfig = config('filament_navigation.groups');

        foreach ($navigationConfig as $group => $settings) {
            if (in_array($resourceClass, $settings['resources']) && isset($settings['order'])) {
                return $settings['order'];
            }
        }

        return null;
    }
}
