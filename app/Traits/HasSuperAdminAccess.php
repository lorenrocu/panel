<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

trait HasSuperAdminAccess
{
    protected static array $accessConfig = [
        'App\Filament\Resources\AtributoPersonalizadoResource' => [
            'roles' => ['admin'],
            'actions' => [
                'admin' => ['view', 'create', 'edit', 'delete'],
            ],
        ],
        'App\Filament\Resources\PlantillaResource' => [
            'roles' => ['admin', 'staff', 'client'],
            'actions' => [
                'admin' => ['view', 'create', 'edit', 'delete'],
                'staff' => ['view', 'create', 'edit', 'delete'],
                'client' => ['view', 'create', 'edit', 'delete'],
            ],
        ],
        'App\Filament\Resources\SegmentoResource' => [
            'roles' => ['admin', 'staff', 'client'],
            'actions' => [
                'admin' => ['view', 'create', 'edit', 'delete'],
                'staff' => ['view', 'create', 'edit', 'delete'],
                'client' => ['view', 'create', 'edit', 'delete'],
            ],
        ],
    ];

    // Controla si un recurso debe estar registrado en la navegación (mostrar en el menú)
    public static function shouldRegisterNavigation(): bool
    {
        return static::hasAccess('view');
    }

    // Controla si un usuario puede ver cualquier registro del recurso
    public static function canViewAny(): bool
    {
        return static::hasAccess('view');
    }

    // Controla si un usuario puede editar un registro específico
    public static function canEdit(Model $record): bool
    {
        return static::hasAccess('edit');
    }

    // Controla si un usuario puede eliminar un registro específico
    public static function canDelete(Model $record): bool
    {
        return static::hasAccess('delete');
    }

    // Controla si un usuario puede crear un nuevo registro
    public static function canCreate(): bool
    {
        return static::hasAccess('create');
    }

    // Método central para verificar acceso basado en la configuración y la acción solicitada
    protected static function hasAccess(string $action): bool
    {
        $resourceClass = static::class;

        if (auth()->user()) {
            $userRole = auth()->user()->getRoleNames()->first(); // Tomar el primer rol del usuario

            // Si el recurso está en la configuración, verificamos si el usuario tiene uno de los roles necesarios
            if (isset(static::$accessConfig[$resourceClass])) {
                $config = static::$accessConfig[$resourceClass];

                // Verificar si el rol del usuario está autorizado a acceder a este recurso
                if (in_array($userRole, $config['roles'])) {
                    // Si la acción se especifica directamente en el array 'actions' como en la mayoría de los recursos
                    if (is_array($config['actions']) && isset($config['actions'][$userRole])) {
                        return in_array($action, $config['actions'][$userRole]);
                    } elseif (is_array($config['actions'])) {
                        return in_array($action, $config['actions']);
                    }
                }
            }
        }

        return false;
    }
}
