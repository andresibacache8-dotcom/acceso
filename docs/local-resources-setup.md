# Guía para configurar recursos locales

## 1. Instalación de Bootstrap 5

1. Descargar Bootstrap 5 desde: https://getbootstrap.com/docs/5.0/getting-started/download/
2. Crear carpeta `../bootstrap/` al mismo nivel que la carpeta acceso
3. Descomprimir el archivo y colocar los archivos CSS y JS en la carpeta creada
4. Estructura recomendada:
   - `../bootstrap/css/bootstrap.min.css`
   - `../bootstrap/js/bootstrap.bundle.min.js`

## 2. Configuración de Bootstrap Icons

1. Descargar Bootstrap Icons desde: https://icons.getbootstrap.com/
   - Botón "Download" en la página principal
2. Crear carpeta `vendor/bootstrap-icons/` dentro de la carpeta acceso
3. Descomprimir el archivo y copiar:
   - El archivo `bootstrap-icons.css` como `bootstrap-icons.min.css` en la carpeta creada
   - La carpeta `fonts` dentro de la carpeta bootstrap-icons

## 3. Configuración de Font Awesome

1. Descargar Font Awesome desde: https://fontawesome.com/download (versión gratuita)
2. Crear carpeta `vendor/font-awesome/` dentro de la carpeta acceso
3. Descomprimir el archivo y copiar:
   - La carpeta `css` que contiene `all.min.css`
   - La carpeta `webfonts` con todos los archivos de fuentes

## 4. Configuración de Animate.css

1. Descargar Animate.css desde: https://github.com/animate-css/animate.css/
   - Ir a "Releases" y descargar la última versión
2. Crear carpeta `vendor/animate-css/` dentro de la carpeta acceso
3. Copiar el archivo `animate.min.css` en esta carpeta

## 5. Configuración de la fuente Inter

1. Descargar la fuente Inter desde: https://fonts.google.com/specimen/Inter
   - Hacer clic en "Download family"
2. Crear carpeta `vendor/fonts/inter/` dentro de la carpeta acceso
3. Descomprimir los archivos y convertir los archivos TTF a WOFF2:
   - Usar una herramienta online como: https://www.fontsquirrel.com/tools/webfont-generator
   - O descargar versiones WOFF2 desde: https://github.com/rsms/inter/tree/master/docs/font-files
4. Copiar los archivos WOFF2 a la carpeta `vendor/fonts/inter/`
5. Ya se ha creado un archivo CSS para cargar las fuentes en `vendor/fonts/inter/inter.css`

## 6. Archivos HTML modificados

Se han creado dos archivos con referencias locales:
- `index-local.html`: Versión de index.html con recursos locales
- `login-local.html`: Versión de login.html con recursos locales

## 7. Estructura final de carpetas

```
acceso/
├── vendor/
│   ├── bootstrap-icons/
│   │   ├── bootstrap-icons.min.css
│   │   └── fonts/
│   ├── font-awesome/
│   │   ├── css/
│   │   │   └── all.min.css
│   │   └── webfonts/
│   ├── animate-css/
│   │   └── animate.min.css
│   └── fonts/
│       └── inter/
│           ├── inter.css
│           └── *.woff2 (archivos de fuentes)
├── index-local.html
└── login-local.html
```

## 8. Instrucciones de uso

1. Completar la descarga de todos los recursos como se indica arriba
2. Renombrar `index-local.html` a `index.html` y `login-local.html` a `login.html`, o actualizar los enlaces correspondientes
3. Verificar que todos los recursos se carguen correctamente
4. En caso de problemas de ruta, ajustar las rutas en los archivos HTML según sea necesario