const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

let opciones;
const superficies = new Map();
const $ = elemento => superficies.get(elemento) || ({ select2: config => { opciones = config; } });
$.fn = { select2: { defaults: { defaults: {
    matcher: (params, data) => !params.term || data.text.toUpperCase().includes(params.term.toUpperCase()) ? data : null
} } } };
const contexto = { window: {}, $ };
vm.runInNewContext(fs.readFileSync('src/js/busqueda-productos.js', 'utf8'), contexto);
const buscador = contexto.window.BusquedaProductos;
const nombre = 'CLAVOS DE MEDIA PULGADA 1.5';
for (const consulta of ['clavos 1.5', '1.5 clavos', 'clavos media', 'CLÁVOS 1,5']) {
    assert.equal(buscador.nivel(consulta, nombre, false), 1, consulta);
}
for (const consulta of ['cavos media 1.5', 'clvaos 1.5', 'clavoz 1.5']) {
    assert.equal(buscador.nivel(consulta, nombre, false), 2, consulta);
}
for (const consulta of ['cavos 15', 'cavos 1', 'clavos 2.5', 'tuercas 1.5', 'clavos media 1.5 azul']) {
    assert.equal(buscador.nivel(consulta, nombre, false), 3, consulta);
}
assert.equal(buscador.nivel('codigo 123', nombre, true), 0, 'Conserva coincidencias anteriores');
assert.equal(buscador.nivel('', nombre, false), 0);
assert.equal(buscador.nivel('te', 'DE', false), 3, 'No corrige palabras cortas');
buscador.configurarSelector({});
const productos = [
    { id: '1', text: 'CLAVOS DE MEDIA PULGADA 1.5' },
    { id: '2', text: 'CAVOS DE MEDIA 1.5' },
    { id: '3', text: 'CLAVOS 1.5' },
    { id: '4', text: 'CLAVOS DE MEDIA 15' }
];
const resultados = opciones.sorter(productos.map(p => opciones.matcher({ term: 'clavos 1.5' }, p)).filter(Boolean));
assert.deepEqual(Array.from(resultados, p => p.id), ['3', '1', '2']);
assert.equal(opciones.matcher({ term: 'producto' }, { id: '0', text: '--seleccione un producto--' }), null);
assert.equal(opciones.matcher({ term: '' }, productos[0]).id, '1');
assert.equal(productos[0].prioridadBusqueda, undefined, 'No altera datos originales');
// Integracion con el filtro, orden y ciclo de vida de la tabla.
const eventos = {};
const entrada = {
    off() { return this; },
    on(nombre, callback) { eventos.entrada = callback; return this; }
};
const nodo = {};
const contenedor = {};
superficies.set(contenedor, { find: () => entrada });
superficies.set(nodo, {
    on(nombre, callback) { eventos[nombre.split('.')[0]] = callback; return this; },
    off() { return this; }
});
$.fn.dataTable = { ext: { search: [], internal: {
    _fnFilterCreateSearch: consulta => new RegExp(consulta.split(' ').map(p => '(?=.*' + p.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')').join(''), 'i')
} } };
const settings = { nTable: nodo, aiDisplay: [] };
const filas = productos.map(p => ['', '', p.text]);
const tabla = {
    settings: () => [settings],
    table: () => ({ container: () => contenedor }),
    search: () => tabla,
    draw() {
        settings.aiDisplay = filas.map((_, i) => i).filter(i => $.fn.dataTable.ext.search[0](settings, filas[i], i));
        eventos.preDraw();
    }
};
buscador.configurarTabla(tabla);
eventos.entrada.call({ value: 'clavos 1.5' });
assert.deepEqual(settings.aiDisplay, [0, 2, 1], 'Coincidencias anteriores de DataTables primero');
assert.equal($.fn.dataTable.ext.search[0]({}, ['', '', 'OTRO'], 0), true, 'No afecta otras tablas');
eventos.entrada.call({ value: 'cavos media 1.5' });
assert.deepEqual(settings.aiDisplay, [1, 0]);
eventos.entrada.call({ value: '' });
assert.deepEqual(settings.aiDisplay, [0, 1, 2, 3], 'Limpiar restaura todos los productos');
eventos.destroy();
assert.equal($.fn.dataTable.ext.search.length, 0);
console.log('Busqueda de productos: palabras, errores, medidas, prioridad, tabla y selector correctos.');
