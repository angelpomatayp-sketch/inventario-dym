@extends('pdf.layout')

@section('title', 'Vale de Salida ' . $vale->numero)

@section('styles')
<style>
    .titulo-doc {
        text-align: center;
        margin: 8px 0 14px 0;
    }
    .titulo-doc h2 {
        font-size: 17px;
        font-weight: bold;
        color: #1e40af;
        letter-spacing: 2px;
        text-transform: uppercase;
    }
    .titulo-doc .numero {
        font-size: 13px;
        font-weight: bold;
        color: #374151;
        margin-top: 3px;
    }

    .info-grid {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12px;
        border: 1px solid #d1d5db;
        border-radius: 4px;
    }
    .info-grid td {
        padding: 5px 10px;
        border-bottom: 1px solid #e5e7eb;
        font-size: 10px;
        vertical-align: top;
    }
    .info-grid tr:last-child td {
        border-bottom: none;
    }
    .info-grid .lbl {
        font-weight: bold;
        color: #374151;
        width: 120px;
        background-color: #f9fafb;
    }
    .info-grid .val {
        color: #1f2937;
    }

    .estado-PENDIENTE  { background:#fef3c7; color:#92400e; padding:2px 7px; border-radius:3px; font-weight:bold; font-size:9px; }
    .estado-ENTREGADO  { background:#d1fae5; color:#065f46; padding:2px 7px; border-radius:3px; font-weight:bold; font-size:9px; }
    .estado-PARCIAL    { background:#dbeafe; color:#1e3a8a; padding:2px 7px; border-radius:3px; font-weight:bold; font-size:9px; }
    .estado-ANULADO    { background:#e5e7eb; color:#374151; padding:2px 7px; border-radius:3px; font-weight:bold; font-size:9px; }

    .section-title {
        font-size: 10px;
        font-weight: bold;
        color: white;
        background-color: #1e40af;
        padding: 4px 10px;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    table.items {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 14px;
    }
    table.items th {
        background-color: #1e3a8a;
        color: white;
        padding: 6px 8px;
        text-align: left;
        font-size: 9px;
        text-transform: uppercase;
    }
    table.items th.num  { text-align: center; width: 28px; }
    table.items th.right { text-align: right; }
    table.items td {
        padding: 5px 8px;
        border-bottom: 1px solid #e5e7eb;
        font-size: 10px;
        vertical-align: top;
    }
    table.items tr:nth-child(even) td { background-color: #f9fafb; }
    table.items td.num   { text-align: center; }
    table.items td.right { text-align: right; }

    .motivo-box {
        border: 1px solid #d1d5db;
        padding: 7px 10px;
        margin-bottom: 12px;
        border-radius: 3px;
        background: #f9fafb;
        font-size: 10px;
        color: #374151;
    }

    .firmas-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 35px;
    }
    .firmas-table td {
        width: 33%;
        text-align: center;
        padding: 0 20px;
        vertical-align: bottom;
    }
    .firma-linea {
        border-top: 1px solid #374151;
        padding-top: 5px;
        margin-top: 45px;
    }
    .firma-nombre {
        font-weight: bold;
        font-size: 10px;
        color: #1f2937;
    }
    .firma-cargo {
        font-size: 9px;
        color: #6b7280;
        margin-top: 2px;
    }
</style>
@endsection

@section('content')

{{-- TÍTULO --}}
<div class="titulo-doc">
    <h2>Vale de Salida</h2>
    <div class="numero">N° {{ $vale->numero }}</div>
</div>

{{-- INFORMACIÓN GENERAL --}}
<div class="section-title">Información del Vale</div>
<table class="info-grid">
    <tr>
        <td class="lbl">Fecha:</td>
        <td class="val">{{ \Carbon\Carbon::parse($vale->fecha)->format('d/m/Y') }}</td>
        <td class="lbl">Registrado por:</td>
        <td class="val">{{ $vale->despachador->nombre }}</td>
    </tr>
    <tr>
        <td class="lbl">Almacén:</td>
        <td class="val">{{ $vale->almacen->nombre }}</td>
        <td class="lbl">Centro de Costo:</td>
        <td class="val">{{ $vale->centroCosto->nombre }}</td>
    </tr>
    @if($vale->requisicion)
    <tr>
        <td class="lbl">Requisición:</td>
        <td class="val" colspan="3">{{ $vale->requisicion->numero }}</td>
    </tr>
    @endif
    <tr>
        <td class="lbl">Receptor:</td>
        <td class="val">{{ $vale->receptor_nombre }}</td>
        <td class="lbl">DNI Receptor:</td>
        <td class="val">{{ $vale->receptor_dni ?: '-' }}</td>
    </tr>
</table>

@if($vale->motivo)
<div class="section-title">Motivo</div>
<div class="motivo-box">{{ $vale->motivo }}</div>
@endif

@if($vale->observaciones)
<div class="section-title">Observaciones</div>
<div class="motivo-box">{{ $vale->observaciones }}</div>
@endif

{{-- DETALLE DE PRODUCTOS --}}
<div class="section-title">Detalle de Materiales ({{ count($vale->detalles) }} ítems)</div>
<table class="items">
    <thead>
        <tr>
            <th class="num">#</th>
            <th style="width:75px">Código</th>
            <th>Descripción</th>
            <th style="width:55px">Unidad</th>
            <th class="right" style="width:80px">Cant. Solicitada</th>
            <th class="right" style="width:80px">Cant. Entregada</th>
        </tr>
    </thead>
    <tbody>
        @foreach($vale->detalles as $i => $detalle)
        <tr>
            <td class="num">{{ $i + 1 }}</td>
            <td>{{ $detalle->producto->codigo }}</td>
            <td>{{ $detalle->producto->nombre }}</td>
            <td>{{ $detalle->producto->unidad_medida }}</td>
            <td class="right">{{ number_format((float)$detalle->cantidad_solicitada, 2) }}</td>
            <td class="right">{{ number_format((float)$detalle->cantidad_entregada, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- FIRMAS --}}
<table class="firmas-table">
    <tr>
        <td style="width:50%">
            <div class="firma-linea">
                <div class="firma-nombre">{{ $vale->despachador->nombre }}</div>
                <div class="firma-cargo">Registrado por / Despachador</div>
            </div>
        </td>
        <td style="width:50%">
            <div class="firma-linea">
                <div class="firma-nombre">{{ $vale->receptor_nombre }}</div>
                <div class="firma-cargo">Receptor / Conforme</div>
            </div>
        </td>
    </tr>
</table>

@endsection
