<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1f2933;
            font-size: 10px;
            line-height: 1.35;
        }

        .header {
            border-bottom: 2px solid #23436b;
            margin-bottom: 14px;
            padding-bottom: 10px;
        }

        .logo {
            max-width: 130px;
            max-height: 70px;
        }

        h1 {
            color: #23436b;
            font-size: 20px;
            margin: 0 0 4px;
        }

        h2 {
            color: #23436b;
            font-size: 14px;
            margin: 0 0 8px;
        }

        .visit {
            border: 1px solid #c8d1dc;
            margin-bottom: 14px;
            padding: 10px;
            page-break-inside: avoid;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .meta-table th,
        .meta-table td {
            border: 1px solid #d9e0e8;
            padding: 4px 5px;
            text-align: left;
            vertical-align: top;
        }

        .meta-table th {
            background: #eef3f8;
            color: #23436b;
            width: 24%;
        }

        .photo-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .photo-table td {
            width: 33.33%;
            border: 1px solid #d9e0e8;
            padding: 6px;
            text-align: center;
            vertical-align: top;
        }

        .photo-label {
            display: block;
            color: #23436b;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .photo {
            max-width: 170px;
            max-height: 125px;
        }

        .empty {
            color: #7b8794;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        @if ($image)
            <img class="logo" src="data:image/png;base64,{{ $image }}" alt="Logo">
        @endif
        <h1>Reporte de Visitas por Fecha</h1>
        <div>Del {{ $from }} al {{ $to }}</div>
        <div>Total de visitas: {{ $visits->count() }}</div>
    </div>

    @forelse ($visits as $visit)
        <div class="visit">
            <h2>Visita #{{ $visit->id }} - {{ $visit->Primer_Nombre }} {{ $visit->Segundo_Nombre }} {{ $visit->Primer_Apellido }} {{ $visit->Segundo_Apellido }}</h2>

            <table class="meta-table">
                <tr>
                    <th>Ingreso</th>
                    <td>{{ $visit->fechaingreso }}</td>
                    <th>Egreso</th>
                    <td>{{ $visit->fechaegreso ?: 'Pendiente' }}</td>
                </tr>
                <tr>
                    <th>Tipo licencia</th>
                    <td>{{ $visit->tipoLicencia }}</td>
                    <th>Documento</th>
                    <td>{{ $visit->numeroDocumento }}</td>
                </tr>
                <tr>
                    <th>DPI / CUI</th>
                    <td>{{ $visit->cui }}</td>
                    <th>Fecha vencimiento</th>
                    <td>{{ $visit->Fecha_vencimiento }}</td>
                </tr>
                <tr>
                    <th>Fecha nacimiento</th>
                    <td>{{ $visit->Fecha_nac }}</td>
                    <th>Telefono emergencia</th>
                    <td>{{ $visit->tel_emergencia }}</td>
                </tr>
                <tr>
                    <th>Placa</th>
                    <td>{{ $visit->Placa }}</td>
                    <th>Cono</th>
                    <td>{{ $visit->cono }}</td>
                </tr>
                <tr>
                    <th>Residente</th>
                    <td>{{ optional($visit->residente)->Codigo }} - {{ optional($visit->residente)->Nombres }} {{ optional($visit->residente)->Apellidos }}</td>
                    <th>Direccion</th>
                    <td>{{ optional($visit->residente)->Direccion }}</td>
                </tr>
                <tr>
                    <th>Telefono residente</th>
                    <td>{{ optional($visit->residente)->Telefono }}</td>
                    <th>Acceso telefonico</th>
                    <td>{{ optional($visit->residente)->accesotel }}</td>
                </tr>
                <tr>
                    <th>Creado por</th>
                    <td>{{ $visit->created_by }}</td>
                    <th>Actualizado por</th>
                    <td>{{ $visit->updated_by }}</td>
                </tr>
            </table>

            <table class="photo-table">
                <tr>
                    @if ($config->enable_fotovisitante == 1)
                        <td>
                            <span class="photo-label">Visitante</span>
                            @if ($photos[$visit->id]['visitante'])
                                <img class="photo" src="{{ $photos[$visit->id]['visitante'] }}" alt="Visitante">
                            @else
                                <span class="empty">Sin foto</span>
                            @endif
                        </td>
                    @endif
                    @if ($config->enable_fotolicencia == 1)
                        <td>
                            <span class="photo-label">Licencia</span>
                            @if ($photos[$visit->id]['licencia'])
                                <img class="photo" src="{{ $photos[$visit->id]['licencia'] }}" alt="Licencia">
                            @else
                                <span class="empty">Sin foto</span>
                            @endif
                        </td>
                    @endif
                    @if (($config->enable_fotoplaca ?? 1) == 1)
                        <td>
                            <span class="photo-label">Placa</span>
                            @if ($photos[$visit->id]['placa'])
                                <img class="photo" src="{{ $photos[$visit->id]['placa'] }}" alt="Placa">
                            @else
                                <span class="empty">Sin foto</span>
                            @endif
                        </td>
                    @endif
                </tr>
            </table>
        </div>
    @empty
        <p>No hay visitas en el rango seleccionado.</p>
    @endforelse
</body>
</html>
