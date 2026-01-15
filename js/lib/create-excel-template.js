// Script para generar una plantilla Excel para importación de vehículos
// Ejecutar con Node.js: node create-excel-template.js

const XLSX = require('xlsx');
const fs = require('fs');
const path = require('path');

// Crear un nuevo libro de trabajo
const wb = XLSX.utils.book_new();

// Definir los datos de ejemplo
const data = [
  {
    patente: 'AB1234',
    marca: 'TOYOTA',
    modelo: 'COROLLA',
    tipo: 'FISCAL',
    personalNrRut: '12345678-9',
    acceso_permanente: '1',
    fecha_expiracion: ''
  },
  {
    patente: 'CD5678',
    marca: 'NISSAN',
    modelo: 'QASHQAI',
    tipo: 'FUNCIONARIO',
    personalNrRut: '98765432-1',
    acceso_permanente: '0',
    fecha_expiracion: '2025-12-31'
  },
  {
    patente: 'EF9012',
    marca: 'MAZDA',
    modelo: 'CX-5',
    tipo: 'VISITA',
    personalNrRut: '87654321-0',
    acceso_permanente: '0',
    fecha_expiracion: '2025-11-15'
  },
  {
    patente: 'GH3456',
    marca: 'FORD',
    modelo: 'RANGER',
    tipo: 'EMPRESA',
    personalNrRut: '76543210-9',
    acceso_permanente: '1',
    fecha_expiracion: ''
  }
];

// Crear hoja de cálculo con los datos
const ws = XLSX.utils.json_to_sheet(data);

// Añadir hoja al libro
XLSX.utils.book_append_sheet(wb, ws, 'Vehiculos');

// Escribir archivo
const outputFilePath = path.join(__dirname, '..', 'templates', 'plantilla_vehiculos.xlsx');
XLSX.writeFile(wb, outputFilePath);

console.log(`Archivo Excel creado en: ${outputFilePath}`);