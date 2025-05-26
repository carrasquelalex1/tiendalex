/**
 * Script de diagnóstico para los selectores de color
 * Este script ayuda a identificar problemas con la inicialización de los color pickers
 */

console.log('=== DIAGNÓSTICO DE COLOR PICKERS ===');

// Verificar librerías
function verificarLibrerias() {
    console.log('1. Verificando librerías...');
    
    const jquery = typeof jQuery !== 'undefined';
    const materialize = typeof M !== 'undefined';
    const spectrum = typeof jQuery !== 'undefined' && typeof jQuery.fn.spectrum !== 'undefined';
    
    console.log('   - jQuery:', jquery ? '✓ Cargado' : '✗ No cargado');
    console.log('   - Materialize:', materialize ? '✓ Cargado' : '✗ No cargado');
    console.log('   - Spectrum:', spectrum ? '✓ Cargado' : '✗ No cargado');
    
    return jquery && materialize && spectrum;
}

// Verificar elementos DOM
function verificarElementos() {
    console.log('2. Verificando elementos DOM...');
    
    const colorPickers = document.querySelectorAll('.color-picker');
    console.log('   - Elementos .color-picker encontrados:', colorPickers.length);
    
    colorPickers.forEach((element, index) => {
        console.log(`   - Elemento ${index + 1}: ID=${element.id}, Valor=${element.value}`);
    });
    
    return colorPickers.length > 0;
}

// Verificar inicialización de Spectrum
function verificarSpectrum() {
    console.log('3. Verificando inicialización de Spectrum...');
    
    if (typeof jQuery === 'undefined') {
        console.log('   ✗ jQuery no está disponible');
        return false;
    }
    
    const $colorPickers = jQuery('.color-picker');
    const inicializados = $colorPickers.filter('.sp-input').length;
    
    console.log(`   - Color pickers inicializados: ${inicializados}/${$colorPickers.length}`);
    
    $colorPickers.each(function(index) {
        const $this = jQuery(this);
        const esSpectrum = $this.hasClass('sp-input');
        console.log(`   - Elemento ${index + 1}: ${esSpectrum ? '✓ Inicializado' : '✗ No inicializado'}`);
    });
    
    return inicializados === $colorPickers.length;
}

// Función principal de diagnóstico
function ejecutarDiagnostico() {
    console.log('Iniciando diagnóstico...');
    
    const libreriasOK = verificarLibrerias();
    const elementosOK = verificarElementos();
    const spectrumOK = verificarSpectrum();
    
    console.log('=== RESUMEN ===');
    console.log('Librerías:', libreriasOK ? '✓ OK' : '✗ ERROR');
    console.log('Elementos DOM:', elementosOK ? '✓ OK' : '✗ ERROR');
    console.log('Spectrum:', spectrumOK ? '✓ OK' : '✗ ERROR');
    
    if (libreriasOK && elementosOK && !spectrumOK) {
        console.log('🔧 Intentando reinicializar Spectrum...');
        reinicializarSpectrum();
    }
    
    return libreriasOK && elementosOK && spectrumOK;
}

// Función para reinicializar Spectrum
function reinicializarSpectrum() {
    if (typeof jQuery === 'undefined') {
        console.log('No se puede reinicializar: jQuery no disponible');
        return;
    }
    
    try {
        // Destruir instancias existentes
        jQuery('.color-picker').spectrum('destroy');
        
        // Reinicializar
        jQuery('.color-picker').spectrum({
            showInput: true,
            preferredFormat: "hex",
            showPalette: true,
            showAlpha: false,
            showInitial: true,
            showButtons: true,
            containerClassName: 'color-picker-container',
            palette: [
                ["#2c3e50", "#34495e", "#2980b9", "#27ae60", "#c0392b", "#f39c12"],
                ["#1abc9c", "#3498db", "#9b59b6", "#e74c3c", "#f1c40f", "#ecf0f1"],
                ["#16a085", "#2980b9", "#8e44ad", "#c0392b", "#f39c12", "#bdc3c7"],
                ["#3498db", "#2ecc71", "#e74c3c", "#f1c40f", "#9b59b6", "#ecf0f1"]
            ],
            change: function(color) {
                console.log('Color cambiado:', color.toHexString());
                if (typeof updatePreview === 'function') {
                    updatePreview();
                }
            },
            move: function(color) {
                if (typeof updatePreview === 'function') {
                    updatePreview();
                }
            }
        });
        
        console.log('✓ Spectrum reinicializado correctamente');
        
        // Verificar nuevamente
        setTimeout(verificarSpectrum, 500);
        
    } catch (error) {
        console.error('Error al reinicializar Spectrum:', error);
    }
}

// Ejecutar diagnóstico cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(ejecutarDiagnostico, 1000);
    });
} else {
    setTimeout(ejecutarDiagnostico, 1000);
}

// También ejecutar cuando la ventana esté completamente cargada
window.addEventListener('load', function() {
    setTimeout(ejecutarDiagnostico, 2000);
});

// Exponer funciones globalmente para debugging manual
window.colorPickerDiagnostics = {
    ejecutarDiagnostico,
    verificarLibrerias,
    verificarElementos,
    verificarSpectrum,
    reinicializarSpectrum
};

console.log('Script de diagnóstico cargado. Usa window.colorPickerDiagnostics para debugging manual.');
