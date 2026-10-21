<?php include_once __DIR__ . '/../templates/content-header.php'; ?>



<!-- Main content -->
<section class="content" id="productos">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">

        <!-- /.card -->

        <div class="card">
          <div class="card-header">
            <div class="row justify-content-between">
              <div class="col-4">
                <h3 class="card-title">Productos</h3>
              </div>
              <div class="col-4 d-flex justify-content-end">
                <button type="button" id="registrar" class="btn bg-hover-azul text-white toolMio">
                  Registrar Producto
                </button>
              </div>
            </div>
          </div>
          <!-- /.card-header -->
          <div class="card-body">
            <table id="tabla" class="display responsive table w-100 table-bordered table-striped">
              <thead>
                <tr>
                  <th>#</th>
                  <th>CODIGO</th>
                  <th>PRODUCTO</th>
                  <th>STOCK</th>

                  <th>PRECIO COMPRA</th>
                  <th>PRECIO VENTA</th>


                  <th class="text-center">Acciones</th>
                </tr>
              </thead>

            </table>
          </div>
          <!-- /.card-body -->
        </div>
        <!-- /.card -->
      </div>
      <!-- /.col -->
    </div>
    <!-- /.row -->
  </div>
  <!-- /.container-fluid -->
</section>



<div class="modal fade" id="modal-producto">
  <div class="modal-dialog modal-50rem">
    <div class="modal-content">
      <div class="modal-header bg-azul">
        <h4 class="modal-title text-white">Registrar Producto</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true" class="text-white">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="productoForm">

          <div class="card-body">
            <div class="row">
              <div class="form-group col-md-6">
                <label for="nombre">Nombre</label>
                <input type="text" name="nombre" class="form-control" id="nombre" placeholder="Nombre del Producto">
              </div>
              <div class="form-group col-md-6">
                <label for="codigo">Código</label>
                <input type="text" name="codigo" class="form-control" id="codigo" placeholder="Código del Producto">
              </div>
            </div>
            <div class="row">
              <div class="form-group col-md-6">
                <label for="categoria_id">Categoria del producto</label>
                <select class="form-control selectCategoria" id="categoria_id" style="width: 100%;">


                </select>
              </div>
              <div class="form-group col-md-6">
                <label for="provedor_id">Proveedor del Producto</label>
                <select class="form-control selectProveedor" id="proveedor_id" style="width: 100%;">


                </select>
              </div>
            </div>
            <div class="row">
              <div class="form-group col-md-6">
                <label for="stock">Stock</label>
                <input type="number" name="stock" class="form-control" id="stock" placeholder="Stock con el que inicia el producto">
              </div>
              <div class="form-group col-md-6">
                <label for="stock_minimo">Stock Mínimo</label>
                <input type="number" name="stock_minimo" class="form-control" id="stock_minimo" placeholder="Stock mínimo en inventario">
              </div>
            </div>
            <hr class="bg-azul">
            <div class="row">
              <div class="form-group col-md-6">
                <label for="precio_paquete">Precio paquete</label>
                <input type="text" name="precio_paquete" class="form-control" id="precio_paquete" placeholder="Precio del paquete completo">
              </div>
              <div class="form-group col-md-6">
                <label for="unidades"> Unidades por paquete</label>
                <input type="number" name="unidades" class="form-control" id="unidades" placeholder="Unidades por paquete">
              </div>
            </div>
            <hr class="bg-azul">
            <div class="row">
              <div class="form-group col-md-6">
                <label for="precio_compra">Precio de compra</label>
                <input type="text" name="precio_compra" class="form-control" id="precio_compra" placeholder="Precio de Compra">
              </div>
              <div class="form-group col-md-6">
                <label for="precio_venta">Precio de venta</label>
                <input type="text" name="precio_venta" class="form-control" id="precio_venta" placeholder="Precio de venta">
              </div>
            </div>
            <div class="row justify-content-end">

              <div class="form-group col-md-6">
                <label for="porcentaje_venta">Porcentaje para precio de venta</label>
                <input type="number" name="porcentaje_venta" class="form-control" id="porcentaje_venta" placeholder="Calcular el precio de venta con porcentaje">
              </div>
            </div>
          </div>



          <div class="card-footer">
            <div class="row justify-content-between">
              <div class="col-6">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
              </div>
              <div class="">
                <button type="submit" class="btn bg-hover-azul text-white" id="btnSubmit">Enviar</button>
              </div>
            </div>
          </div>

        </form>
      </div>

    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div>

<!-- Modal para actualizar stock -->

<div class="modal fade" id="modal-stock">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-azul">
        <h4 class="modal-title text-white">Actualizar Stock</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true" class="text-white">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="stockForm">

          <div class="card-body">
         
              <div class="form-group">
                <label for="nombre_producto">Nombre del Proucto</label>
                <input type="text" name="nombre_producto" class="form-control" readonly id="nombre_producto">
              </div>
              
    
         
          
              <div class="form-group">
                <label for="nuevo_stock">Cantidad Adquirida</label>
                <input type="number" name="nuevo_stock" class="form-control" id="nuevo_stock" placeholder="Digite la cantidad adquirida">
              </div>
           
          
            <hr class="bg-azul">
            <div class="row">
              <div class="form-group col-md-6">
                <label for="precio_paquete_nuevo">Precio paquete</label>
                <input type="text" name="precio_paquete_nuevo" class="form-control" id="precio_paquete_nuevo" placeholder="Precio del paquete completo">
              </div>
              <div class="form-group col-md-6">
                <label for="unidades_nuevo"> Unidades por paquete</label>
                <input type="number" name="unidades_nuevo" class="form-control" id="unidades_nuevo" placeholder="Unidades por paquete">
              </div>
            </div>
            <hr class="bg-azul">
            <div class="row">
              <div class="form-group col-md-6">
                <label for="precio_compra_nuevo">Precio de compra</label>
                <input type="text" name="precio_compra_nuevo" class="form-control" id="precio_compra_nuevo" placeholder="Precio de Compra">
              </div>
            
            </div>
           
          </div>



          <div class="card-footer">
            <div class="row justify-content-between">
              <div class="col-6">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
              </div>
              <div class="">
                <button type="submit" class="btn bg-hover-azul text-white" id="btnSubmitNewStock">Actualizar</button>
              </div>
            </div>
          </div>

        </form>
      </div>

    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div>