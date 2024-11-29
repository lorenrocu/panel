<?php

return [
    'groups' => [
        'Configuraciones' => [
            'resources' => [
                'App\Filament\Resources\ConfiguracionResource',

                //Menu de CLientes
                'App\Filament\Resources\ConfiguracionEmpresaResource',
                'App\Filament\Resources\ColaboradorResource',
            ],
            'icon' => 'heroicon-o-cog',
            'order' => 1, // Prioridad de orden para el grupo
        ],
        'Gestión de Empresas' => [
            'resources' => [
                'App\Filament\Resources\UserResource',
                'App\Filament\Resources\EmpresaResource',
                'App\Filament\Resources\EmpresaUserResource',
            ],
            'icon' => 'heroicon-o-user',
            'order' => 2,
        ],
        'Gestión de Planes' => [
            'resources' => [
                'App\Filament\Resources\PlanResource',
            ],
            'icon' => 'heroicon-o-cube-transparent',
            'order' => 3,
        ],

        //Nuevos
        'Marketing' => [
            'resources' => [
                'App\Filament\Resources\PlantillaResource',
                'App\Filament\Resources\SegmentoResource',
            ],
            'icon' => 'heroicon-o-cube-transparent',
            'order' => 4,
        ],
    ],
];
