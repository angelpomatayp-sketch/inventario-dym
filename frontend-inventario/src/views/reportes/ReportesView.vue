<template>
  <div class="reportes-view">
    <!-- Header -->
    <div class="flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="text-2xl font-bold m-0">Reportes</h1>
        <p class="text-500 mt-1">Genera y exporta reportes del sistema</p>
      </div>
    </div>

    <!-- Tabs de Reportes -->
    <Tabs v-model:value="activeTab" class="reportes-tabs">
      <TabList>
        <Tab value="0">Kardex Valorizado</Tab>
        <Tab value="1">Inventario Valorizado</Tab>
        <Tab value="2">Stock Bajo</Tab>
        <Tab value="3">Consumo por Área</Tab>
        <Tab value="4">Top Productos</Tab>
        <Tab value="5">Movimientos</Tab>
      </TabList>
      <TabPanels>
        <!-- Tab Kardex -->
        <TabPanel value="0">
        <div class="grid">
          <!-- Filtros Kardex -->
          <div class="col-12">
            <Card>
              <template #content>
                <div class="grid">
                  <div class="col-12 md:col-3">
                    <label class="block mb-2 font-medium">Producto</label>
                    <Select
                      v-model="filtrosKardex.producto_id"
                      :options="productos"
                      optionLabel="nombre"
                      optionValue="id"
                      placeholder="Todos los productos"
                      filter
                      showClear
                      class="w-full"
                    />
                  </div>
                  <div class="col-12 md:col-3">
                    <label class="block mb-2 font-medium">Almacén</label>
                    <Select
                      v-model="filtrosKardex.almacen_id"
                      :options="almacenes"
                      optionLabel="nombre"
                      optionValue="id"
                      placeholder="Todos los almacenes"
                      showClear
                      class="w-full"
                    />
                  </div>
                  <div class="col-12 md:col-2">
                    <label class="block mb-2 font-medium">Fecha Inicio</label>
                    <DatePicker
                      v-model="filtrosKardex.fecha_inicio"
                      dateFormat="dd/mm/yy"
                      class="w-full"
                    />
                  </div>
                  <div class="col-12 md:col-2">
                    <label class="block mb-2 font-medium">Fecha Fin</label>
                    <DatePicker
                      v-model="filtrosKardex.fecha_fin"
                      dateFormat="dd/mm/yy"
                      class="w-full"
                    />
                  </div>
                  <div class="col-12 md:col-2 flex align-items-end gap-2">
                    <Button
                      label="Generar"
                      icon="pi pi-search"
                      @click="generarKardex"
                      :loading="loadingKardex"
                    />
                    <Button
                      icon="pi pi-file-excel"
                      severity="success"
                      @click="exportarKardexExcel"
                      :disabled="!kardexData.registros?.length"
                      v-tooltip="'Exportar Excel'"
                    />
                    <Button
                      icon="pi pi-file-pdf"
                      severity="danger"
                      @click="exportarKardexPdf"
                      :disabled="!kardexData.registros?.length"
                      v-tooltip="'Exportar PDF'"
                    />
                  </div>
                </div>
              </template>
            </Card>
          </div>

          <!-- Totales Kardex -->
          <div class="col-12" v-if="kardexData.totales">
            <div class="grid">
              <div class="col-12 md:col-2">
                <Card class="bg-green-50">
                  <template #content>
                    <div class="text-center">
                      <div class="text-500 mb-1">Total Entradas</div>
                      <div class="text-2xl font-bold text-green-600">{{ kardexData.totales.total_entradas }}</div>
                    </div>
                  </template>
                </Card>
              </div>
              <div class="col-12 md:col-2">
                <Card class="bg-red-50">
                  <template #content>
                    <div class="text-center">
                      <div class="text-500 mb-1">Total Salidas</div>
                      <div class="text-2xl font-bold text-red-600">{{ kardexData.totales.total_salidas }}</div>
                    </div>
                  </template>
                </Card>
              </div>
              <div class="col-12 md:col-2">
                <Card class="bg-green-50">
                  <template #content>
                    <div class="text-center">
                      <div class="text-500 mb-1">Valor Entradas</div>
                      <div class="text-xl font-bold text-green-600">S/ {{ formatNumber(kardexData.totales.valor_entradas) }}</div>
                    </div>
                  </template>
                </Card>
              </div>
              <div class="col-12 md:col-2">
                <Card class="bg-red-50">
                  <template #content>
                    <div class="text-center">
                      <div class="text-500 mb-1">Valor Salidas</div>
                      <div class="text-xl font-bold text-red-600">S/ {{ formatNumber(kardexData.totales.valor_salidas) }}</div>
                    </div>
                  </template>
                </Card>
              </div>
              <div class="col-12 md:col-2">
                <Card class="bg-blue-50">
                  <template #content>
                    <div class="text-center">
                      <div class="text-500 mb-1">Saldo Cantidad</div>
                      <div class="text-2xl font-bold text-blue-600">{{ kardexData.totales.saldo_final_cantidad }}</div>
                    </div>
                  </template>
                </Card>
              </div>
              <div class="col-12 md:col-2">
                <Card class="bg-blue-50">
                  <template #content>
                    <div class="text-center">
                      <div class="text-500 mb-1">Saldo Valor</div>
                      <div class="text-xl font-bold text-blue-600">S/ {{ formatNumber(kardexData.totales.saldo_final_valor) }}</div>
                    </div>
                  </template>
                </Card>
              </div>
            </div>
          </div>

          <!-- Tabla Kardex -->
          <div class="col-12">
            <DataTable
              :value="kardexData.registros"
              :loading="loadingKardex"
              stripedRows
              size="small"
              class="p-datatable-sm"
              :paginator="kardexData.registros?.length > 20"
              :rows="20"
              emptyMessage="Seleccione filtros y presione Generar"
            >
              <Column field="fecha" header="Fecha" style="width: 100px">
                <template #body="{ data }">
                  {{ formatDate(data.fecha) }}
                </template>
              </Column>
              <Column field="producto.codigo" header="Código" style="width: 100px" />
              <Column field="producto.nombre" header="Producto" />
              <Column field="almacen.nombre" header="Almacén" style="width: 120px" />
              <Column field="tipo_movimiento" header="Tipo" style="width: 100px">
                <template #body="{ data }">
                  <Tag :severity="data.tipo_movimiento === 'ENTRADA' ? 'success' : (data.tipo_movimiento === 'SALIDA' ? 'danger' : 'secondary')" :value="data.tipo_movimiento" />
                </template>
              </Column>
              <Column field="documento" header="Documento" style="width: 120px" />
              <Column header="Entrada Cant." style="width: 100px; text-align: right">
                <template #body="{ data }">
                  {{ data.tipo_movimiento === 'ENTRADA' ? data.cantidad : '' }}
                </template>
              </Column>
              <Column header="Salida Cant." style="width: 100px; text-align: right">
                <template #body="{ data }">
                  {{ data.tipo_movimiento === 'SALIDA' ? data.cantidad : '' }}
                </template>
              </Column>
              <Column field="saldo_cantidad" header="Saldo Cant." style="width: 100px; text-align: right" />
              <Column header="Saldo Valor" style="width: 120px; text-align: right">
                <template #body="{ data }">
                  S/ {{ formatNumber(data.saldo_valor) }}
                </template>
              </Column>
            </DataTable>
          </div>
        </div>
      </TabPanel>

      <!-- Tab Inventario -->
        <TabPanel value="1">
        <div class="grid">
          <!-- Filtros Inventario -->
          <div class="col-12">
            <Card>
              <template #content>
                <div class="grid">
                  <div class="col-12 md:col-3">
                    <label class="block mb-2 font-medium">Producto</label>
                    <InputText
                      v-model="filtrosInventario.search"
                      placeholder="Buscar por código o nombre"
                      class="w-full"
                    />
                  </div>
                  <div class="col-12 md:col-3">
                    <label class="block mb-2 font-medium">Familia</label>
                    <Select
                      v-model="filtrosInventario.familia_id"
                      :options="familias"
                      optionLabel="nombre"
                      optionValue="id"
                      placeholder="Todas las familias"
                      showClear
                      class="w-full"
                    />
                  </div>
                  <div class="col-12 md:col-3">
                    <label class="block mb-2 font-medium">Almacén</label>
                    <Select
                      v-model="filtrosInventario.almacen_id"
                      :options="almacenes"
                      optionLabel="nombre"
                      optionValue="id"
                      placeholder="Todos los almacenes"
                      showClear
                      class="w-full"
                    />
                  </div>
                  <div class="col-12 md:col-3">
                    <label class="block mb-2 font-medium">&nbsp;</label>
                    <div class="flex align-items-center gap-2">
                      <Checkbox v-model="filtrosInventario.solo_stock" inputId="soloStock" :binary="true" />
                      <label for="soloStock">Solo con stock</label>
                    </div>
                  </div>
                  <div class="col-12 flex align-items-end gap-2">
                    <Button
                      label="Generar"
                      icon="pi pi-search"
                      @click="generarInventario"
                      :loading="loadingInventario"
                    />
                    <Button
                      icon="pi pi-file-excel"
                      severity="success"
                      @click="exportarInventarioExcel"
                      :disabled="!inventarioData.productos?.length"
                      v-tooltip="'Exportar Excel'"
                    />
                    <Button
                      icon="pi pi-file-pdf"
                      severity="danger"
                      @click="exportarInventarioPdf"
                      :disabled="!inventarioData.productos?.length"
                      v-tooltip="'Exportar PDF'"
                    />
                  </div>
                </div>
              </template>
            </Card>
          </div>

          <!-- Tabla Inventario -->
          <div class="col-12">
            <DataTable
              :value="inventarioData.productos"
              :loading="loadingInventario"
              stripedRows
              size="small"
              :paginator="inventarioData.productos?.length > 20"
              :rows="20"
              emptyMessage="Presione Generar para ver el inventario"
            >
              <Column field="codigo" header="Código" style="width: 100px" />
              <Column field="nombre" header="Producto" />
              <Column field="familia" header="Familia" style="width: 150px" />
              <Column field="unidad_medida" header="Unidad" style="width: 80px" />
              <Column field="stock_fisico" header="Stock Físico" style="width: 110px; text-align: right" />
              <Column field="stock_prestado" header="Prestado" style="width: 90px; text-align: right" />
              <Column field="stock_total_activo" header="Total Activo" style="width: 110px; text-align: right" />
              <Column field="stock_minimo" header="Stock Mín." style="width: 100px; text-align: right" />
              <Column header="Costo Prom." style="width: 120px; text-align: right">
                <template #body="{ data }">
                  S/ {{ formatNumber(data.costo_promedio, 4) }}
                </template>
              </Column>
              <Column header="Valor Total" style="width: 120px; text-align: right">
                <template #body="{ data }">
                  S/ {{ formatNumber(data.valor_total) }}
                </template>
              </Column>
              <Column field="estado_stock" header="Estado" style="width: 100px">
                <template #body="{ data }">
                  <Tag
                    :severity="data.estado_stock === 'NORMAL' ? 'success' : (data.estado_stock === 'BAJO' ? 'warn' : (data.estado_stock === 'CON_PRESTAMO' ? 'info' : 'danger'))"
                    :value="data.estado_stock === 'SIN_STOCK' ? 'Sin Stock' : (data.estado_stock === 'BAJO' ? 'Bajo' : (data.estado_stock === 'CON_PRESTAMO' ? 'Con préstamo' : 'Normal'))"
                  />
                </template>
              </Column>
            </DataTable>
          </div>
        </div>
      </TabPanel>

      <!-- Tab Stock Bajo -->
      <TabPanel value="2">
        <div class="grid">
          <div class="col-12">
            <Card>
              <template #content>
                <Button
                  label="Actualizar"
                  icon="pi pi-refresh"
                  @click="cargarStockBajo"
                  :loading="loadingStockBajo"
                />
              </template>
            </Card>
          </div>

          <!-- Totales Stock Bajo -->
          <div class="col-12" v-if="stockBajoData.totales">
            <div class="grid">
              <div class="col-12 md:col-3">
                <Card class="bg-orange-50">
                  <template #content>
                    <div class="text-center">
                      <div class="text-500 mb-1">Total Productos</div>
                      <div class="text-2xl font-bold text-orange-600">{{ stockBajoData.totales.total_productos }}</div>
                    </div>
                  </template>
                </Card>
              </div>
              <div class="col-12 md:col-3">
                <Card class="bg-red-50">
                  <template #content>
                    <div class="text-center">
                      <div class="text-500 mb-1">Críticos (Sin Stock)</div>
                      <div class="text-2xl font-bold text-red-600">{{ stockBajoData.totales.criticos }}</div>
                    </div>
                  </template>
                </Card>
              </div>
              <div class="col-12 md:col-3">
                <Card class="bg-yellow-50">
                  <template #content>
                    <div class="text-center">
                      <div class="text-500 mb-1">Stock Bajo</div>
                      <div class="text-2xl font-bold text-yellow-600">{{ stockBajoData.totales.bajos }}</div>
                    </div>
                  </template>
                </Card>
              </div>
              <div class="col-12 md:col-3">
                <Card class="bg-blue-50">
                  <template #content>
                    <div class="text-center">
                      <div class="text-500 mb-1">Costo Reposición</div>
                      <div class="text-xl font-bold text-blue-600">S/ {{ formatNumber(stockBajoData.totales.costo_reposicion_total) }}</div>
                    </div>
                  </template>
                </Card>
              </div>
            </div>
          </div>

          <!-- Tabla Stock Bajo -->
          <div class="col-12">
            <DataTable
              :value="stockBajoData.productos"
              :loading="loadingStockBajo"
              stripedRows
              size="small"
              :paginator="stockBajoData.productos?.length > 20"
              :rows="20"
              emptyMessage="No hay productos con stock bajo"
            >
              <Column field="codigo" header="Código" style="width: 100px" />
              <Column field="nombre" header="Producto" />
              <Column field="familia" header="Familia" style="width: 150px" />
              <Column field="stock_actual" header="Stock Actual" style="width: 100px; text-align: right" />
              <Column field="stock_minimo" header="Stock Mínimo" style="width: 100px; text-align: right" />
              <Column field="diferencia" header="Faltante" style="width: 100px; text-align: right">
                <template #body="{ data }">
                  <span class="text-red-600 font-bold">{{ data.diferencia }}</span>
                </template>
              </Column>
              <Column field="estado" header="Estado" style="width: 100px">
                <template #body="{ data }">
                  <Tag :severity="data.estado === 'CRÍTICO' ? 'danger' : 'warn'" :value="data.estado" />
                </template>
              </Column>
              <Column header="Costo Reposición" style="width: 130px; text-align: right">
                <template #body="{ data }">
                  S/ {{ formatNumber(data.costo_reposicion) }}
                </template>
              </Column>
            </DataTable>
          </div>
        </div>
      </TabPanel>

      <!-- Tab Consumo por Centro de Costo -->
      <TabPanel value="3">
        <div class="grid">
          <!-- Filtros Consumo -->
          <div class="col-12">
            <Card>
              <template #content>
                <div class="grid">
                  <div class="col-12 md:col-3">
                    <label class="block mb-2 font-medium">Centro de Costo</label>
                    <Select
                      v-model="filtrosConsumo.centro_costo_id"
                      :options="centrosCosto"
                      optionLabel="nombre"
                      optionValue="id"
                      placeholder="Todos"
                      showClear
                      class="w-full"
                    />
                  </div>
                  <div class="col-12 md:col-2">
                    <label class="block mb-2 font-medium">Fecha Inicio</label>
                    <DatePicker
                      v-model="filtrosConsumo.fecha_inicio"
                      dateFormat="dd/mm/yy"
                      class="w-full"
                    />
                  </div>
                  <div class="col-12 md:col-2">
                    <label class="block mb-2 font-medium">Fecha Fin</label>
                    <DatePicker
                      v-model="filtrosConsumo.fecha_fin"
                      dateFormat="dd/mm/yy"
                      class="w-full"
                    />
                  </div>
                  <div class="col-12 md:col-2 flex align-items-end">
                    <Button
                      label="Generar"
                      icon="pi pi-search"
                      @click="generarConsumo"
                      :loading="loadingConsumo"
                    />
                  </div>
                </div>
              </template>
            </Card>
          </div>

          <!-- Totales Consumo -->
          <div class="col-12" v-if="consumoData.totales">
            <div class="grid">
              <div class="col-12 md:col-4">
                <Card class="bg-blue-50">
                  <template #content>
                    <div class="text-center">
                      <div class="text-500 mb-1">Centros de Costo</div>
                      <div class="text-2xl font-bold text-blue-600">{{ consumoData.totales.total_centros }}</div>
                    </div>
                  </template>
                </Card>
              </div>
              <div class="col-12 md:col-4">
                <Card class="bg-green-50">
                  <template #content>
                    <div class="text-center">
                      <div class="text-500 mb-1">Total Vales</div>
                      <div class="text-2xl font-bold text-green-600">{{ consumoData.totales.total_vales }}</div>
                    </div>
                  </template>
                </Card>
              </div>
              <div class="col-12 md:col-4">
                <Card class="bg-purple-50">
                  <template #content>
                    <div class="text-center">
                      <div class="text-500 mb-1">Valor Total</div>
                      <div class="text-xl font-bold text-purple-600">S/ {{ formatNumber(consumoData.totales.valor_total) }}</div>
                    </div>
                  </template>
                </Card>
              </div>
            </div>
          </div>

          <!-- Tabla Resumen por Centro -->
          <div class="col-12">
            <h3 class="mt-0">Resumen por Centro de Costo</h3>
            <DataTable
              :value="consumoData.resumen"
              :loading="loadingConsumo"
              stripedRows
              size="small"
              emptyMessage="Seleccione filtros y presione Generar"
            >
              <Column field="centro_costo_codigo" header="Código" style="width: 100px" />
              <Column field="centro_costo_nombre" header="Centro de Costo" />
              <Column field="total_vales" header="N° Vales" style="width: 100px; text-align: right" />
              <Column field="total_items" header="Items" style="width: 100px; text-align: right" />
              <Column header="Valor Total" style="width: 150px; text-align: right">
                <template #body="{ data }">
                  <span class="font-bold">S/ {{ formatNumber(data.valor_total) }}</span>
                </template>
              </Column>
            </DataTable>
          </div>

          <!-- Detalle si hay centro seleccionado -->
          <div class="col-12" v-if="consumoData.detalle?.length">
            <h3>Detalle de Productos Consumidos</h3>
            <DataTable
              :value="consumoData.detalle"
              stripedRows
              size="small"
              :paginator="consumoData.detalle?.length > 15"
              :rows="15"
            >
              <Column field="codigo" header="Código" style="width: 100px" />
              <Column field="nombre" header="Producto" />
              <Column field="unidad_medida" header="Unidad" style="width: 80px" />
              <Column field="cantidad" header="Cantidad" style="width: 100px; text-align: right" />
              <Column header="Costo Prom." style="width: 120px; text-align: right">
                <template #body="{ data }">
                  S/ {{ formatNumber(data.costo_promedio, 4) }}
                </template>
              </Column>
              <Column header="Valor Total" style="width: 130px; text-align: right">
                <template #body="{ data }">
                  S/ {{ formatNumber(data.valor_total) }}
                </template>
              </Column>
            </DataTable>
          </div>
        </div>
      </TabPanel>

      <!-- Tab Top Productos -->
      <TabPanel value="4">
        <div class="grid">
          <!-- Filtros Top -->
          <div class="col-12">
            <Card>
              <template #content>
                <div class="grid">
                  <div class="col-12 md:col-2">
                    <label class="block mb-2 font-medium">Fecha Inicio</label>
                    <DatePicker
                      v-model="filtrosTop.fecha_inicio"
                      dateFormat="dd/mm/yy"
                      class="w-full"
                    />
                  </div>
                  <div class="col-12 md:col-2">
                    <label class="block mb-2 font-medium">Fecha Fin</label>
                    <DatePicker
                      v-model="filtrosTop.fecha_fin"
                      dateFormat="dd/mm/yy"
                      class="w-full"
                    />
                  </div>
                  <div class="col-12 md:col-2">
                    <label class="block mb-2 font-medium">Cantidad</label>
                    <InputNumber
                      v-model="filtrosTop.limite"
                      :min="5"
                      :max="50"
                      class="w-full"
                    />
                  </div>
                  <div class="col-12 md:col-2 flex align-items-end">
                    <Button
                      label="Generar"
                      icon="pi pi-search"
                      @click="generarTopProductos"
                      :loading="loadingTop"
                    />
                  </div>
                </div>
              </template>
            </Card>
          </div>

          <!-- Tabla Top Productos -->
          <div class="col-12">
            <DataTable
              :value="topProductosData"
              :loading="loadingTop"
              stripedRows
              size="small"
              emptyMessage="Seleccione filtros y presione Generar"
            >
              <Column header="#" style="width: 50px">
                <template #body="{ index }">
                  <Tag :severity="index < 3 ? 'danger' : (index < 5 ? 'warn' : 'info')" :value="index + 1" />
                </template>
              </Column>
              <Column field="codigo" header="Código" style="width: 100px" />
              <Column field="nombre" header="Producto" />
              <Column field="familia" header="Familia" style="width: 150px" />
              <Column field="unidad_medida" header="Unidad" style="width: 80px" />
              <Column field="cantidad_total" header="Cant. Total" style="width: 100px; text-align: right">
                <template #body="{ data }">
                  <span class="font-bold">{{ formatNumber(data.cantidad_total, 0) }}</span>
                </template>
              </Column>
              <Column field="veces_solicitado" header="Veces Solicitado" style="width: 120px; text-align: right" />
              <Column header="Valor Total" style="width: 130px; text-align: right">
                <template #body="{ data }">
                  S/ {{ formatNumber(data.valor_total) }}
                </template>
              </Column>
            </DataTable>
          </div>
        </div>
      </TabPanel>

      <!-- Tab Movimientos -->
      <TabPanel value="5">
        <div class="grid">
          <!-- Filtros Movimientos -->
          <div class="col-12">
            <Card>
              <template #content>
                <div class="grid">
                  <div class="col-12 md:col-2">
                    <label class="block mb-2 font-medium">Tipo</label>
                    <Select
                      v-model="filtrosMovimientos.tipo"
                      :options="tiposMovimiento"
                      optionLabel="label"
                      optionValue="value"
                      placeholder="Todos"
                      showClear
                      class="w-full"
                    />
                  </div>
                  <div class="col-12 md:col-2">
                    <label class="block mb-2 font-medium">Almacén</label>
                    <Select
                      v-model="filtrosMovimientos.almacen_id"
                      :options="almacenes"
                      optionLabel="nombre"
                      optionValue="id"
                      placeholder="Todos"
                      showClear
                      class="w-full"
                    />
                  </div>
                  <div class="col-12 md:col-2">
                    <label class="block mb-2 font-medium">Fecha Inicio</label>
                    <DatePicker
                      v-model="filtrosMovimientos.fecha_inicio"
                      dateFormat="dd/mm/yy"
                      class="w-full"
                    />
                  </div>
                  <div class="col-12 md:col-2">
                    <label class="block mb-2 font-medium">Fecha Fin</label>
                    <DatePicker
                      v-model="filtrosMovimientos.fecha_fin"
                      dateFormat="dd/mm/yy"
                      class="w-full"
                    />
                  </div>
                  <div class="col-12 md:col-4 flex align-items-end gap-2">
                    <Button
                      label="Generar"
                      icon="pi pi-search"
                      @click="generarMovimientos"
                      :loading="loadingMovimientos"
                    />
                    <Button
                      icon="pi pi-file-excel"
                      severity="success"
                      @click="exportarMovimientosExcel"
                      :disabled="!movimientosData.movimientos?.length"
                      v-tooltip="'Exportar Excel'"
                    />
                    <Button
                      icon="pi pi-file-pdf"
                      severity="danger"
                      @click="exportarMovimientosPdf"
                      :disabled="!movimientosData.movimientos?.length"
                      v-tooltip="'Exportar PDF'"
                    />
                  </div>
                </div>
              </template>
            </Card>
          </div>

          <!-- Totales Movimientos -->
          <div class="col-12" v-if="movimientosData.totales">
            <div class="grid">
              <div class="col-12 md:col-4">
                <Card class="bg-blue-50">
                  <template #content>
                    <div class="text-center">
                      <div class="text-500 mb-1">Total Movimientos</div>
                      <div class="text-2xl font-bold text-blue-600">{{ movimientosData.totales.total_movimientos }}</div>
                    </div>
                  </template>
                </Card>
              </div>
              <div class="col-12 md:col-4">
                <Card class="bg-green-50">
                  <template #content>
                    <div class="text-center">
                      <div class="text-500 mb-1">Entradas</div>
                      <div class="text-2xl font-bold text-green-600">{{ movimientosData.totales.entradas }}</div>
                    </div>
                  </template>
                </Card>
              </div>
              <div class="col-12 md:col-4">
                <Card class="bg-red-50">
                  <template #content>
                    <div class="text-center">
                      <div class="text-500 mb-1">Salidas</div>
                      <div class="text-2xl font-bold text-red-600">{{ movimientosData.totales.salidas }}</div>
                    </div>
                  </template>
                </Card>
              </div>
            </div>
          </div>

          <!-- Tabla Movimientos -->
          <div class="col-12">
            <DataTable
              :value="movimientosData.movimientos"
              :loading="loadingMovimientos"
              stripedRows
              size="small"
              :paginator="movimientosData.movimientos?.length > 20"
              :rows="20"
              emptyMessage="Seleccione filtros y presione Generar"
            >
              <Column field="fecha" header="Fecha" style="width: 100px">
                <template #body="{ data }">
                  {{ formatDate(data.fecha) }}
                </template>
              </Column>
              <Column field="tipo" header="Tipo" style="width: 150px">
                <template #body="{ data }">
                  <Tag :severity="getTipoMovimientoSeverity(data.tipo)" :value="data.tipo" />
                </template>
              </Column>
              <Column field="documento" header="Documento" style="width: 150px" />
              <Column field="almacen.nombre" header="Almacén" style="width: 150px" />
              <Column header="Items" style="width: 80px; text-align: right">
                <template #body="{ data }">
                  {{ data.detalles?.length || 0 }}
                </template>
              </Column>
              <Column field="usuario.nombre" header="Usuario" style="width: 150px" />
              <Column field="observaciones" header="Observaciones" />
            </DataTable>
          </div>
        </div>
      </TabPanel>
      </TabPanels>
    </Tabs>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import api from '@/services/api'

// PrimeVue Components
import Button from 'primevue/button'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'
import Checkbox from 'primevue/checkbox'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import ProgressSpinner from 'primevue/progressspinner'

const toast = useToast()

// Tabs
const activeTab = ref("0")

// Loading states
const loadingKardex = ref(false)
const loadingInventario = ref(false)
const loadingStockBajo = ref(false)
const loadingConsumo = ref(false)
const loadingTop = ref(false)
const loadingMovimientos = ref(false)

// Datos maestros
const productos = ref([])
const almacenes = ref([])
const familias = ref([])
const centrosCosto = ref([])

// Filtros
const filtrosKardex = ref({
  producto_id: null,
  almacen_id: null,
  fecha_inicio: new Date(new Date().getFullYear(), new Date().getMonth(), 1),
  fecha_fin: new Date()
})

const filtrosInventario = ref({
  search: '',
  familia_id: null,
  almacen_id: null,
  solo_stock: false
})

const filtrosConsumo = ref({
  centro_costo_id: null,
  fecha_inicio: new Date(new Date().getFullYear(), new Date().getMonth(), 1),
  fecha_fin: new Date()
})

const filtrosTop = ref({
  fecha_inicio: new Date(new Date().getFullYear(), new Date().getMonth(), 1),
  fecha_fin: new Date(),
  limite: 10
})

const filtrosMovimientos = ref({
  tipo: null,
  almacen_id: null,
  fecha_inicio: new Date(new Date().getFullYear(), new Date().getMonth(), 1),
  fecha_fin: new Date()
})

// Datos de reportes
const kardexData = ref({ registros: [], totales: null })
const inventarioData = ref({ productos: [], totales: null })
const stockBajoData = ref({ productos: [], totales: null })
const consumoData = ref({ resumen: [], detalle: [], totales: null })
const topProductosData = ref([])
const movimientosData = ref({ movimientos: [], totales: null })

// Tipos de movimiento
const tiposMovimiento = [
  { label: 'Compra', value: 'COMPRA' },
  { label: 'Venta', value: 'VENTA' },
  { label: 'Ajuste Entrada', value: 'AJUSTE_ENTRADA' },
  { label: 'Ajuste Salida', value: 'AJUSTE_SALIDA' },
  { label: 'Transferencia', value: 'TRANSFERENCIA' },
  { label: 'Vale Salida', value: 'VALE_SALIDA' },
  { label: 'Devolución', value: 'DEVOLUCION' }
]

// Formatters
const formatDate = (date) => {
  if (!date) return '-'
  const d = new Date(date)
  return d.toLocaleDateString('es-PE')
}

const formatNumber = (num, decimals = 2) => {
  if (num === null || num === undefined) return '0.00'
  return Number(num).toLocaleString('es-PE', {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals
  })
}

const formatDateForApi = (date) => {
  if (!date) return null
  const d = new Date(date)
  return d.toISOString().split('T')[0]
}

const normalizarTipoKardex = (registro) => {
  const tipo = String(registro?.tipo_movimiento || registro?.tipo_operacion || '').toUpperCase()
  if (['ENTRADA', 'AJUSTE_POSITIVO', 'SALDO_INICIAL', 'TRANSFERENCIA_ENTRADA'].includes(tipo)) return 'ENTRADA'
  if (['SALIDA', 'AJUSTE_NEGATIVO', 'TRANSFERENCIA_SALIDA'].includes(tipo)) return 'SALIDA'
  if (String(registro?.movimiento?.tipo || '').toUpperCase() === 'ENTRADA') return 'ENTRADA'
  if (String(registro?.movimiento?.tipo || '').toUpperCase() === 'SALIDA') return 'SALIDA'
  return '-'
}

const normalizarRegistroKardex = (registro) => {
  const tipoMovimiento = normalizarTipoKardex(registro)
  return {
    ...registro,
    tipo_movimiento: tipoMovimiento,
    documento: registro?.documento || registro?.documento_referencia || registro?.movimiento?.documento_referencia || '-',
    saldo_valor: registro?.saldo_valor ?? registro?.saldo_costo_total ?? 0
  }
}

const getTipoMovimientoSeverity = (tipo) => {
  const entradas = ['COMPRA', 'AJUSTE_ENTRADA', 'TRANSFERENCIA_ENTRADA', 'DEVOLUCION']
  const salidas = ['VENTA', 'CONSUMO', 'AJUSTE_SALIDA', 'TRANSFERENCIA_SALIDA', 'VALE_SALIDA']
  if (entradas.includes(tipo)) return 'success'
  if (salidas.includes(tipo)) return 'danger'
  return 'info'
}

// Cargar datos maestros
const cargarDatosMaestros = async () => {
  try {
    const [prodRes, almRes, famRes, ccRes] = await Promise.all([
      api.get('/inventario/productos', { params: { per_page: 1000 } }),
      api.get('/administracion/almacenes'),
      api.get('/inventario/familias'),
      api.get('/administracion/centros-costo')
    ])
    productos.value = prodRes.data.data?.data || prodRes.data.data || []
    almacenes.value = almRes.data.data?.data || almRes.data.data || []
    familias.value = famRes.data.data?.data || famRes.data.data || []
    centrosCosto.value = ccRes.data.data?.data || ccRes.data.data || []
  } catch (error) {
    console.error('Error cargando datos maestros:', error)
  }
}

// Generar Kardex
const generarKardex = async () => {
  if (!filtrosKardex.value.fecha_inicio || !filtrosKardex.value.fecha_fin) {
    toast.add({ severity: 'warn', summary: 'Atención', detail: 'Seleccione rango de fechas', life: 3000 })
    return
  }
  loadingKardex.value = true
  try {
    const params = {
      producto_id: filtrosKardex.value.producto_id,
      almacen_id: filtrosKardex.value.almacen_id,
      fecha_inicio: formatDateForApi(filtrosKardex.value.fecha_inicio),
      fecha_fin: formatDateForApi(filtrosKardex.value.fecha_fin)
    }
    const response = await api.get('/reportes/kardex', { params })
    const payload = response.data.data || {}
    kardexData.value = {
      ...payload,
      registros: (payload.registros || []).map(normalizarRegistroKardex)
    }
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Error al generar kardex', life: 3000 })
  } finally {
    loadingKardex.value = false
  }
}

// Exportar Kardex Excel
const exportarKardexExcel = async () => {
  try {
    const params = {
      producto_id: filtrosKardex.value.producto_id,
      almacen_id: filtrosKardex.value.almacen_id,
      fecha_inicio: formatDateForApi(filtrosKardex.value.fecha_inicio),
      fecha_fin: formatDateForApi(filtrosKardex.value.fecha_fin)
    }
    const response = await api.get('/reportes/kardex/exportar', {
      params,
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `kardex_${new Date().toISOString().split('T')[0]}.xlsx`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Archivo descargado', life: 3000 })
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Error al exportar', life: 3000 })
  }
}

// Exportar Kardex PDF
const exportarKardexPdf = async () => {
  try {
    const params = {
      producto_id: filtrosKardex.value.producto_id,
      almacen_id: filtrosKardex.value.almacen_id,
      fecha_inicio: formatDateForApi(filtrosKardex.value.fecha_inicio),
      fecha_fin: formatDateForApi(filtrosKardex.value.fecha_fin)
    }
    const response = await api.get('/reportes/kardex/exportar-pdf', {
      params,
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `kardex_${new Date().toISOString().split('T')[0]}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'PDF descargado', life: 3000 })
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Error al exportar PDF', life: 3000 })
  }
}

// Generar Inventario
const generarInventario = async () => {
  loadingInventario.value = true
  try {
    const params = {
      search: filtrosInventario.value.search || undefined,
      familia_id: filtrosInventario.value.familia_id,
      almacen_id: filtrosInventario.value.almacen_id,
      solo_stock: filtrosInventario.value.solo_stock ? 1 : 0
    }
    const response = await api.get('/reportes/inventario', { params })
    inventarioData.value = response.data.data
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Error al generar inventario', life: 3000 })
  } finally {
    loadingInventario.value = false
  }
}

// Exportar Inventario Excel
const exportarInventarioExcel = async () => {
  try {
    const params = {
      search: filtrosInventario.value.search || undefined,
      familia_id: filtrosInventario.value.familia_id,
      almacen_id: filtrosInventario.value.almacen_id
    }
    const response = await api.get('/reportes/inventario/exportar', {
      params,
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `inventario_${new Date().toISOString().split('T')[0]}.xlsx`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Archivo descargado', life: 3000 })
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Error al exportar', life: 3000 })
  }
}

// Exportar Inventario PDF
const exportarInventarioPdf = async () => {
  try {
    const params = {
      search: filtrosInventario.value.search || undefined,
      familia_id: filtrosInventario.value.familia_id,
      almacen_id: filtrosInventario.value.almacen_id
    }
    const response = await api.get('/reportes/inventario/exportar-pdf', {
      params,
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `inventario_${new Date().toISOString().split('T')[0]}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'PDF descargado', life: 3000 })
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Error al exportar PDF', life: 3000 })
  }
}

// Cargar Stock Bajo
const cargarStockBajo = async () => {
  loadingStockBajo.value = true
  try {
    const response = await api.get('/reportes/stock-bajo')
    stockBajoData.value = response.data.data
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Error al cargar stock bajo', life: 3000 })
  } finally {
    loadingStockBajo.value = false
  }
}

// Generar Consumo
const generarConsumo = async () => {
  if (!filtrosConsumo.value.fecha_inicio || !filtrosConsumo.value.fecha_fin) {
    toast.add({ severity: 'warn', summary: 'Atención', detail: 'Seleccione rango de fechas', life: 3000 })
    return
  }
  loadingConsumo.value = true
  try {
    const params = {
      centro_costo_id: filtrosConsumo.value.centro_costo_id,
      fecha_inicio: formatDateForApi(filtrosConsumo.value.fecha_inicio),
      fecha_fin: formatDateForApi(filtrosConsumo.value.fecha_fin)
    }
    const response = await api.get('/reportes/consumo-centro-costo', { params })
    consumoData.value = response.data.data
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Error al generar consumo', life: 3000 })
  } finally {
    loadingConsumo.value = false
  }
}

// Generar Top Productos
const generarTopProductos = async () => {
  if (!filtrosTop.value.fecha_inicio || !filtrosTop.value.fecha_fin) {
    toast.add({ severity: 'warn', summary: 'Atención', detail: 'Seleccione rango de fechas', life: 3000 })
    return
  }
  loadingTop.value = true
  try {
    const params = {
      fecha_inicio: formatDateForApi(filtrosTop.value.fecha_inicio),
      fecha_fin: formatDateForApi(filtrosTop.value.fecha_fin),
      limite: filtrosTop.value.limite
    }
    const response = await api.get('/reportes/top-productos', { params })
    topProductosData.value = response.data.data
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Error al generar top productos', life: 3000 })
  } finally {
    loadingTop.value = false
  }
}

// Generar Movimientos
const generarMovimientos = async () => {
  if (!filtrosMovimientos.value.fecha_inicio || !filtrosMovimientos.value.fecha_fin) {
    toast.add({ severity: 'warn', summary: 'Atención', detail: 'Seleccione rango de fechas', life: 3000 })
    return
  }
  loadingMovimientos.value = true
  try {
    const params = {
      tipo: filtrosMovimientos.value.tipo,
      almacen_id: filtrosMovimientos.value.almacen_id,
      fecha_inicio: formatDateForApi(filtrosMovimientos.value.fecha_inicio),
      fecha_fin: formatDateForApi(filtrosMovimientos.value.fecha_fin)
    }
    const response = await api.get('/reportes/movimientos', { params })
    movimientosData.value = response.data.data
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Error al generar movimientos', life: 3000 })
  } finally {
    loadingMovimientos.value = false
  }
}

// Exportar Movimientos Excel
const exportarMovimientosExcel = async () => {
  try {
    const params = {
      tipo: filtrosMovimientos.value.tipo,
      almacen_id: filtrosMovimientos.value.almacen_id,
      fecha_inicio: formatDateForApi(filtrosMovimientos.value.fecha_inicio),
      fecha_fin: formatDateForApi(filtrosMovimientos.value.fecha_fin)
    }
    const response = await api.get('/reportes/movimientos/exportar', {
      params,
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `movimientos_${new Date().toISOString().split('T')[0]}.xlsx`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Archivo descargado', life: 3000 })
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Error al exportar', life: 3000 })
  }
}

// Exportar Movimientos PDF
const exportarMovimientosPdf = async () => {
  try {
    const params = {
      tipo: filtrosMovimientos.value.tipo,
      almacen_id: filtrosMovimientos.value.almacen_id,
      fecha_inicio: formatDateForApi(filtrosMovimientos.value.fecha_inicio),
      fecha_fin: formatDateForApi(filtrosMovimientos.value.fecha_fin)
    }
    const response = await api.get('/reportes/movimientos/exportar-pdf', {
      params,
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `movimientos_${new Date().toISOString().split('T')[0]}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'PDF descargado', life: 3000 })
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Error al exportar PDF', life: 3000 })
  }
}

onMounted(() => {
  cargarDatosMaestros()
  cargarStockBajo()
})
</script>

<style scoped>
.reportes-view {
  padding: 1.5rem;
}

:deep(.p-tabview-panels) {
  padding: 1rem 0;
}

:deep(.p-card) {
  box-shadow: none;
  border: 1px solid var(--surface-border);
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  background: var(--surface-100);
}
</style>
