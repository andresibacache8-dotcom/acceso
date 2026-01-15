<?php
header('Content-Type: application/json');

// Simulación de datos para desarrollo
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['details'])) {
        $category = $_GET['details'];
        $data = [];
        
        switch ($category) {
            case 'personal':
            case 'personal_trabajando':
            case 'personal_residiendo':
            case 'personal_comision':
            case 'personal_actividades':
                $data = [
                    [
                        'grado' => 'CAP',
                        'nombre' => 'Juan Pérez Gómez',
                        'rut' => '12.345.678-9',
                        'unidad' => 'Operaciones',
                        'fecha_ingreso' => date('Y-m-d H:i:s', strtotime('-3 hours'))
                    ],
                    [
                        'grado' => 'TTE',
                        'nombre' => 'María López Hernández',
                        'rut' => '11.222.333-4',
                        'unidad' => 'Logística',
                        'fecha_ingreso' => date('Y-m-d H:i:s', strtotime('-2 hours'))
                    ],
                    [
                        'grado' => 'SOM',
                        'nombre' => 'Pedro González Martínez',
                        'rut' => '10.987.654-3',
                        'unidad' => 'Administración',
                        'fecha_ingreso' => date('Y-m-d H:i:s', strtotime('-1 hour'))
                    ],
                    [
                        'grado' => 'CRL',
                        'nombre' => 'Ana Rodríguez Sánchez',
                        'rut' => '9.876.543-2',
                        'unidad' => 'Dirección',
                        'fecha_ingreso' => date('Y-m-d H:i:s', strtotime('-30 minutes'))
                    ]
                ];
                break;
                
            case 'vehiculos_funcionario':
            case 'vehiculos_residente':
            case 'vehiculos_visita':
            case 'vehiculos_fiscal':
                $data = [
                    [
                        'patente' => 'AB-1234',
                        'marca' => 'Toyota Corolla',
                        'propietario' => 'Juan Pérez',
                        'fecha_ingreso' => date('Y-m-d H:i:s', strtotime('-3 hours'))
                    ],
                    [
                        'patente' => 'CD-5678',
                        'marca' => 'Nissan Versa',
                        'propietario' => 'María López',
                        'fecha_ingreso' => date('Y-m-d H:i:s', strtotime('-2 hours'))
                    ],
                    [
                        'patente' => 'EF-9012',
                        'marca' => 'Chevrolet Sail',
                        'propietario' => 'Pedro González',
                        'fecha_ingreso' => date('Y-m-d H:i:s', strtotime('-1 hour'))
                    ],
                    [
                        'patente' => 'GH-3456',
                        'marca' => 'Kia Rio',
                        'propietario' => 'Ana Rodríguez',
                        'fecha_ingreso' => date('Y-m-d H:i:s', strtotime('-30 minutes'))
                    ]
                ];
                break;
                
            case 'visitas':
                $data = [
                    [
                        'nombre' => 'Carlos Meneses',
                        'rut' => '15.234.567-8',
                        'tipo' => 'Visita',
                        'poc' => 'Juan Pérez',
                        'fecha_ingreso' => date('Y-m-d H:i:s', strtotime('-3 hours'))
                    ],
                    [
                        'nombre' => 'Alejandra Torres',
                        'rut' => '14.321.654-9',
                        'tipo' => 'Familiar',
                        'poc' => 'María López',
                        'fecha_ingreso' => date('Y-m-d H:i:s', strtotime('-2 hours'))
                    ],
                    [
                        'nombre' => 'Roberto Fuentes',
                        'rut' => '16.789.012-3',
                        'tipo' => 'Empresa',
                        'poc' => 'Pedro González',
                        'fecha_ingreso' => date('Y-m-d H:i:s', strtotime('-1 hour'))
                    ]
                ];
                break;
                
            case 'empresas':
                $data = [
                    [
                        'nombre_empresa' => 'Constructora XYZ',
                        'representante' => 'Roberto Fuentes',
                        'trabajadores' => 3,
                        'fecha_ingreso' => date('Y-m-d H:i:s', strtotime('-3 hours'))
                    ],
                    [
                        'nombre_empresa' => 'Servicios ABC',
                        'representante' => 'Camila Vega',
                        'trabajadores' => 2,
                        'fecha_ingreso' => date('Y-m-d H:i:s', strtotime('-2 hours'))
                    ],
                    [
                        'nombre_empresa' => 'Mantención DEF',
                        'representante' => 'Jorge Silva',
                        'trabajadores' => 4,
                        'fecha_ingreso' => date('Y-m-d H:i:s', strtotime('-1 hour'))
                    ]
                ];
                break;
                
            default:
                $data = [];
        }
        
        echo json_encode($data);
        exit;
    } else {
        // Datos generales del dashboard
        $dashboardData = [
            'personal_general_adentro' => 42,
            'personal_trabajando' => 35,
            'personal_residiendo' => 12,
            'personal_otras_actividades' => 8,
            'personal_en_comision' => 5,
            'empresas_adentro' => 3,
            'visitas_adentro' => 7,
            'vehiculos_funcionario_adentro' => 18,
            'vehiculos_residente_adentro' => 10,
            'vehiculos_visita_adentro' => 4,
            'vehiculos_proveedor_adentro' => 2,
            'vehiculos_fiscal_adentro' => 3
        ];
        
        echo json_encode($dashboardData);
        exit;
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['message' => 'Método no permitido']);
    exit;
}
?>