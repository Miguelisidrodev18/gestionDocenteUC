<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Informe Final - {{ $curso->codigo }} {{ $curso->nombre }}</title>
  <style>
    body{ font-family: Arial, Helvetica, sans-serif; color:#111; }
    h1{ text-align:center; margin:0 0 10px 0; }
    .box{ border:1px solid #222; border-radius:8px; padding:16px; margin-bottom:16px; }
    .row{ display:flex; gap:12px; flex-wrap:wrap; }
    .col{ flex:1 1 220px; }
    table{ border-collapse:collapse; width:100%; font-size:12px; }
    th,td{ border:1px solid #333; padding:6px 8px; text-align:center; }
    th{ background:#f0f0f0; }
    .muted{ color:#666; font-size:12px; }
    .section-title{ font-weight:bold; margin:10px 0 6px; }
    @media print{ .no-print{ display:none; } body{ margin:0; } }
  </style>
</head>
<body>
  <div class="no-print" style="text-align:right; margin:8px 0;">
    <button onclick="window.print()">Imprimir / Guardar PDF</button>
  </div>
  <h1>INFORME FINAL</h1>

  <div class="box">
    <div class="row">
      <div class="col"><strong>Asignatura:</strong> {{ $curso->nombre }}</div>
      <div class="col"><strong>Código:</strong> {{ $curso->codigo }}</div>
      <div class="col"><strong>Responsable:</strong> {{ $informe->responsable ?? ($curso->responsable->name ?? '') }}</div>
      <div class="col"><strong>Fecha de presentación:</strong> {{ optional($informe->fecha_presentacion)->format('Y-m-d') }}</div>
    </div>
  </div>

  <div class="box">
    <div class="section-title">A) Resultados de las evaluaciones</div>
    @php($resultados = (array) ($informe->resultados ?? []))
    <table>
      <thead>
        <tr>
          <th>Sede</th>
          <th>Promedio C1</th>
          <th>Promedio EP</th>
          <th>% Aprobados C1</th>
          <th>% Aprobados EP</th>
          <th>Nota mínima</th>
          <th>Nota máxima</th>
        </tr>
      </thead>
      <tbody>
      @forelse($resultados as $sede => $row)
        <tr>
          <td>{{ $sede }}</td>
          <td>{{ $row['promedio_c1'] ?? '' }}</td>
          <td>{{ $row['ep_promedio'] ?? '' }}</td>
          <td>{{ $row['porcentaje_c1'] ?? '' }}</td>
          <td>{{ $row['porcentaje_ep'] ?? '' }}</td>
          <td>{{ $row['nota_min'] ?? '' }}</td>
          <td>{{ $row['nota_max'] ?? '' }}</td>
        </tr>
      @empty
        <tr><td colspan="7" class="muted">Sin datos</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>

  <div class="box">
    <div class="section-title">B) Oportunidades de mejora para el próximo período</div>
    @php($mejoras = (array) ($informe->mejoras ?? []))
    @forelse($mejoras as $sede => $items)
      <div style="margin-bottom:8px;">
        <strong>{{ $sede }}</strong>
        <div class="muted">{{ is_array($items) ? json_encode($items, JSON_UNESCAPED_UNICODE) : (string) $items }}</div>
      </div>
    @empty
      <div class="muted">Sin datos</div>
    @endforelse
  </div>

  <div class="muted">Generado: {{ now()->format('Y-m-d H:i') }}</div>
</body>
</html>

