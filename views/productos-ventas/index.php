<?php include_once __DIR__ . '/../templates/content-header.php'; ?>



<!-- Main content -->
<section class="content" id="productos-vendidos">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">

                <!-- /.card -->

                <div class="card">
                    <div class="card-header">
                        <div class="row justify-content-between">
                            <div class="col-4">
                                <h3 class="card-title">Productos Vendidos</h3>
                            </div>
                            <div class="col-4">
                                <div class="row">
                                    <div class="col-6">
                                            <span>fecha Inicial</span>
                                            <input type="date" id="fecha-inicial" >
                                    </div>
                                    <div class="col-6">
                                            <span>fecha Final</span>
                                            <input type="date" id="fecha-final">
                                    </div>
                                </div>
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
                             
                                    <th>CANTIDAD</th>
                                    <th>VALOR UNITARIO</th>
                                    <th>TOTAL</th>
                                    <th>FECHA</th>
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



