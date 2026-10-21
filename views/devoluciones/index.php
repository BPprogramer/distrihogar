<?php include_once __DIR__ . '/../templates/content-header.php'; ?>
<section class="content" id="devoluciones" data-csrf="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
  <div class="container-fluid">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Devoluciones</h3>
        <button type="button" id="nueva-devolucion" class="btn bg-hover-azul text-white ml-auto">Nueva devolución</button>
      </div>
      <div class="card-body">
        <p class="text-muted">Consulte las devoluciones y su efecto en la deuda o en caja. Las ventas y los abonos originales se conservan.</p>
        <div id="error-devoluciones" class="alert alert-danger" role="alert" hidden></div>
        <table id="tabla-devoluciones" class="display responsive table w-100 table-bordered table-striped">
          <thead><tr><th>N.º</th><th>Venta</th><th>Cliente</th><th>Fecha</th><th>Valor devuelto</th><th>Reducción de deuda</th><th>Reembolso</th><th>Responsable</th><th>Acciones</th></tr></thead>
        </table>
      </div>
    </div>
  </div>
</section>
<div class="modal fade" id="modal-devolucion" tabindex="-1" aria-labelledby="titulo-devolucion" aria-hidden="true">
  <div class="modal-dialog modal-xl"><div class="modal-content">
    <div class="modal-header bg-azul"><h4 class="modal-title text-white" id="titulo-devolucion">Nueva devolución</h4><button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button></div>
    <form id="form-devolucion">
      <div class="modal-body">
        <div class="form-group">
          <label for="buscar-venta-devolucion">1. Busque la venta por número, cliente o cédula</label>
          <div class="input-group"><input id="buscar-venta-devolucion" class="form-control" placeholder="Escriba al menos 2 caracteres" autocomplete="off"><div class="input-group-append"><button id="buscar-devolucion" type="button" class="btn bg-hover-azul text-white">Buscar</button></div></div>
          <small class="form-text text-muted">Ventas de contado y fiadas, con caja abierta o cerrada.</small>
        </div>
        <div id="resultados-devolucion" class="list-group mb-3" aria-live="polite"></div>
        <div id="contenido-devolucion" hidden>
          <div id="datos-venta-devolucion" class="alert alert-light border"></div>
          <h5>2. Indique qué productos recibe</h5>
          <p class="text-muted">El valor respeta los precios y descuentos de la venta original. «No vuelve al stock» registra el producto recibido como no disponible para vender.</p>
          <div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Producto</th><th>Comprados</th><th>Ya devueltos</th><th>Disponibles para devolver</th><th>Cantidad a devolver</th><th>Destino</th><th>Valor de devolución</th></tr></thead><tbody id="lineas-devolucion"></tbody></table></div>
          <div class="form-group"><label for="motivo-devolucion">Motivo de la devolución</label><textarea id="motivo-devolucion" class="form-control" rows="2" maxlength="500" minlength="3" required placeholder="Explique por qué se devuelve el producto"></textarea></div>
          <h5>3. Revise el resultado</h5>
          <div id="resumen-devolucion" class="alert alert-light border" aria-live="polite"></div>
          <p class="text-muted">Si hay reembolso, se entrega en efectivo y se descuenta de la caja abierta actual. Si solo disminuye la deuda, no sale dinero de caja.</p>
        </div>
        <div id="error-form-devolucion" class="alert alert-danger" role="alert" hidden></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="submit" id="guardar-devolucion" class="btn bg-hover-azul text-white" disabled>Revisar y confirmar</button></div>
    </form>
  </div></div>
</div>
<div class="modal fade" id="modal-detalle-devolucion" tabindex="-1" aria-labelledby="titulo-detalle-devolucion" aria-hidden="true">
  <div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header bg-azul"><h4 id="titulo-detalle-devolucion" class="modal-title text-white">Detalle de devolución</h4><button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button></div><div class="modal-body" id="detalle-devolucion"></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button></div></div></div>
</div>
