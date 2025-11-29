# Ejemplo de Salida JSON - Endpoint `/api/appointments/report`

## Endpoint
```
GET /api/appointments/report
```

## Parámetros de Consulta

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `tipo_fecha` | string | No | `'diario'`, `'mensual'` o `'personalizado'` (default: `'mensual'`) |
| `fecha_inicio` | date | Sí (si `tipo_fecha=personalizado`) | Fecha de inicio del rango (formato: YYYY-MM-DD) |
| `fecha_fin` | date | Sí (si `tipo_fecha=personalizado`) | Fecha de fin del rango (formato: YYYY-MM-DD) |
| `empleado_id` | integer | No | ID del empleado (solo para negocios/admin) |
| `agrupar_por` | string | No | `'dia'`, `'mes'` o `'semana'` (default: `'dia'`) |

## Ejemplos de Uso

### 1. Informe mensual (default)
```
GET /api/appointments/report
```

### 2. Informe diario
```
GET /api/appointments/report?tipo_fecha=diario
```

### 3. Informe personalizado con agrupación por semana
```
GET /api/appointments/report?tipo_fecha=personalizado&fecha_inicio=2025-01-01&fecha_fin=2025-01-31&agrupar_por=semana
```

### 4. Informe de un empleado específico
```
GET /api/appointments/report?tipo_fecha=mensual&empleado_id=5
```

---

## Ejemplo de Respuesta JSON

```json
{
  "resumen": {
    "total_citas": 25,
    "ganancia_total": 3750.00,
    "ganancia_promedio_por_cita": 150.00,
    "periodo": {
      "tipo": "mensual",
      "fecha_inicio": "2025-01-01",
      "fecha_fin": "2025-01-31",
      "agrupar_por": "dia"
    }
  },
  "graficos": {
    "evolucion_temporal": {
      "etiquetas": [
        "2025-01-15",
        "2025-01-16",
        "2025-01-17",
        "2025-01-18",
        "2025-01-19"
      ],
      "cantidad_citas": [3, 5, 2, 4, 6],
      "ganancias": [450.00, 750.00, 300.00, 600.00, 900.00],
      "datos_completos": [
        {
          "fecha": "2025-01-15",
          "cantidad_citas": 3,
          "ganancia": 450.00
        },
        {
          "fecha": "2025-01-16",
          "cantidad_citas": 5,
          "ganancia": 750.00
        },
        {
          "fecha": "2025-01-17",
          "cantidad_citas": 2,
          "ganancia": 300.00
        },
        {
          "fecha": "2025-01-18",
          "cantidad_citas": 4,
          "ganancia": 600.00
        },
        {
          "fecha": "2025-01-19",
          "cantidad_citas": 6,
          "ganancia": 900.00
        }
      ]
    },
    "servicios": {
      "etiquetas": [
        "Corte de cabello",
        "Tinte",
        "Manicure",
        "Pedicure",
        "Tratamiento facial"
      ],
      "cantidades": [10, 8, 5, 2, 1],
      "ganancias": [1500.00, 1200.00, 500.00, 200.00, 350.00],
      "datos_completos": [
        {
          "id": 1,
          "nombre": "Corte de cabello",
          "cantidad": 10,
          "ganancia_total": 1500.00,
          "ganancia_promedio": 150.00
        },
        {
          "id": 2,
          "nombre": "Tinte",
          "cantidad": 8,
          "ganancia_total": 1200.00,
          "ganancia_promedio": 150.00
        },
        {
          "id": 3,
          "nombre": "Manicure",
          "cantidad": 5,
          "ganancia_total": 500.00,
          "ganancia_promedio": 100.00
        }
      ]
    }
  },
  "citas": [
    {
      "id": 1,
      "cliente": {
        "nombre": "María García",
        "email": "maria.garcia@email.com",
        "telefono": "3001234567"
      },
      "servicio": {
        "id": 1,
        "nombre": "Corte de cabello",
        "descripcion": "Corte de cabello profesional",
        "tiempo_estimado": 60
      },
      "precio": 150.00,
      "empleado_asignado": "Juan Pérez",
      "fecha": {
        "inicio": "2025-01-15 10:00:00",
        "fin": "2025-01-15 11:00:00",
        "fecha_solo": "2025-01-15",
        "hora": "10:00"
      },
      "nota": "Cliente prefiere corte corto"
    },
    {
      "id": 2,
      "cliente": {
        "nombre": "Carlos Rodríguez",
        "email": "carlos.rodriguez@email.com",
        "telefono": "3007654321"
      },
      "servicio": {
        "id": 2,
        "nombre": "Tinte",
        "descripcion": "Tinte completo",
        "tiempo_estimado": 120
      },
      "precio": 200.00,
      "empleado_asignado": "Ana López",
      "fecha": {
        "inicio": "2025-01-15 14:00:00",
        "fin": "2025-01-15 16:00:00",
        "fecha_solo": "2025-01-15",
        "hora": "14:00"
      },
      "nota": null
    }
  ],
  "resumen_por_empleado": [
    {
      "nombre": "Juan Pérez",
      "cantidad_citas": 12,
      "ganancia_total": 1800.00,
      "ganancia_promedio": 150.00
    },
    {
      "nombre": "Ana López",
      "cantidad_citas": 10,
      "ganancia_total": 1500.00,
      "ganancia_promedio": 150.00
    },
    {
      "nombre": "Pedro Martínez",
      "cantidad_citas": 3,
      "ganancia_total": 450.00,
      "ganancia_promedio": 150.00
    }
  ],
  "servicios_mas_realizados": [
    {
      "id": 1,
      "nombre": "Corte de cabello",
      "cantidad": 10,
      "ganancia_total": 1500.00,
      "ganancia_promedio": 150.00
    },
    {
      "id": 2,
      "nombre": "Tinte",
      "cantidad": 8,
      "ganancia_total": 1200.00,
      "ganancia_promedio": 150.00
    },
    {
      "id": 3,
      "nombre": "Manicure",
      "cantidad": 5,
      "ganancia_total": 500.00,
      "ganancia_promedio": 100.00
    }
  ]
}
```

---

## Cómo Generar Gráficas con los Datos

### 1. Gráfica de Línea - Evolución Temporal (Ganancias y Cantidad de Citas)

#### Usando Chart.js (Angular/JavaScript)

```typescript
// En tu componente Angular
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

generateLineChart(reportData: any) {
  const ctx = document.getElementById('lineChart') as HTMLCanvasElement;
  
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: reportData.graficos.evolucion_temporal.etiquetas,
      datasets: [
        {
          label: 'Ganancias ($)',
          data: reportData.graficos.evolucion_temporal.ganancias,
          borderColor: 'rgb(75, 192, 192)',
          backgroundColor: 'rgba(75, 192, 192, 0.2)',
          yAxisID: 'y',
          tension: 0.1
        },
        {
          label: 'Cantidad de Citas',
          data: reportData.graficos.evolucion_temporal.cantidad_citas,
          borderColor: 'rgb(255, 99, 132)',
          backgroundColor: 'rgba(255, 99, 132, 0.2)',
          yAxisID: 'y1',
          tension: 0.1
        }
      ]
    },
    options: {
      responsive: true,
      interaction: {
        mode: 'index',
        intersect: false,
      },
      scales: {
        y: {
          type: 'linear',
          display: true,
          position: 'left',
          title: {
            display: true,
            text: 'Ganancias ($)'
          }
        },
        y1: {
          type: 'linear',
          display: true,
          position: 'right',
          title: {
            display: true,
            text: 'Cantidad de Citas'
          },
          grid: {
            drawOnChartArea: false,
          },
        }
      },
      plugins: {
        title: {
          display: true,
          text: 'Evolución de Ganancias y Citas por Fecha'
        },
        legend: {
          display: true,
          position: 'top'
        }
      }
    }
  });
}
```

#### HTML Template

```html
<div class="chart-container">
  <canvas id="lineChart"></canvas>
</div>
```

### 2. Gráfica de Barras - Servicios Más Realizados

```typescript
generateBarChart(reportData: any) {
  const ctx = document.getElementById('barChart') as HTMLCanvasElement;
  
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: reportData.graficos.servicios.etiquetas,
      datasets: [
        {
          label: 'Cantidad de Citas',
          data: reportData.graficos.servicios.cantidades,
          backgroundColor: 'rgba(54, 162, 235, 0.6)',
          borderColor: 'rgba(54, 162, 235, 1)',
          borderWidth: 1
        },
        {
          label: 'Ganancias ($)',
          data: reportData.graficos.servicios.ganancias,
          backgroundColor: 'rgba(255, 206, 86, 0.6)',
          borderColor: 'rgba(255, 206, 86, 1)',
          borderWidth: 1,
          yAxisID: 'y1'
        }
      ]
    },
    options: {
      responsive: true,
      scales: {
        y: {
          beginAtZero: true,
          title: {
            display: true,
            text: 'Cantidad de Citas'
          }
        },
        y1: {
          type: 'linear',
          display: true,
          position: 'right',
          title: {
            display: true,
            text: 'Ganancias ($)'
          },
          grid: {
            drawOnChartArea: false,
          },
        }
      },
      plugins: {
        title: {
          display: true,
          text: 'Servicios Más Realizados'
        },
        legend: {
          display: true,
          position: 'top'
        }
      }
    }
  });
}
```

### 3. Gráfica de Dona - Resumen por Empleado

```typescript
generateDoughnutChart(reportData: any) {
  const ctx = document.getElementById('doughnutChart') as HTMLCanvasElement;
  
  const empleados = reportData.resumen_por_empleado;
  
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: empleados.map((e: any) => e.nombre),
      datasets: [{
        label: 'Ganancias por Empleado',
        data: empleados.map((e: any) => e.ganancia_total),
        backgroundColor: [
          'rgba(255, 99, 132, 0.6)',
          'rgba(54, 162, 235, 0.6)',
          'rgba(255, 206, 86, 0.6)',
          'rgba(75, 192, 192, 0.6)',
          'rgba(153, 102, 255, 0.6)',
        ],
        borderColor: [
          'rgba(255, 99, 132, 1)',
          'rgba(54, 162, 235, 1)',
          'rgba(255, 206, 86, 1)',
          'rgba(75, 192, 192, 1)',
          'rgba(153, 102, 255, 1)',
        ],
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      plugins: {
        title: {
          display: true,
          text: 'Distribución de Ganancias por Empleado'
        },
        legend: {
          display: true,
          position: 'right'
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              const label = context.label || '';
              const value = context.parsed || 0;
              const total = context.dataset.data.reduce((a: number, b: number) => a + b, 0);
              const percentage = ((value / total) * 100).toFixed(1);
              return `${label}: $${value.toFixed(2)} (${percentage}%)`;
            }
          }
        }
      }
    }
  });
}
```

### 4. Ejemplo Completo en Angular Component

```typescript
import { Component, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

@Component({
  selector: 'app-informe',
  templateUrl: './informe.component.html',
  styleUrls: ['./informe.component.css']
})
export class InformeComponent implements OnInit {
  reportData: any = null;
  loading = false;

  constructor(private http: HttpClient) {}

  ngOnInit() {
    this.loadReport();
  }

  loadReport(tipoFecha: string = 'mensual', fechaInicio?: string, fechaFin?: string) {
    this.loading = true;
    let url = '/api/appointments/report?tipo_fecha=' + tipoFecha;
    
    if (tipoFecha === 'personalizado' && fechaInicio && fechaFin) {
      url += `&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;
    }

    this.http.get(url).subscribe({
      next: (data: any) => {
        this.reportData = data;
        this.loading = false;
        this.generateCharts();
      },
      error: (error) => {
        console.error('Error al cargar informe:', error);
        this.loading = false;
      }
    });
  }

  generateCharts() {
    if (!this.reportData) return;

    // Destruir gráficas anteriores si existen
    this.destroyCharts();

    // Generar nuevas gráficas
    setTimeout(() => {
      this.generateLineChart();
      this.generateBarChart();
      if (this.reportData.resumen_por_empleado.length > 0) {
        this.generateDoughnutChart();
      }
    }, 100);
  }

  generateLineChart() {
    // Código de gráfica de línea (ver arriba)
  }

  generateBarChart() {
    // Código de gráfica de barras (ver arriba)
  }

  generateDoughnutChart() {
    // Código de gráfica de dona (ver arriba)
  }

  destroyCharts() {
    // Destruir instancias anteriores de Chart.js
    Chart.getChart('lineChart')?.destroy();
    Chart.getChart('barChart')?.destroy();
    Chart.getChart('doughnutChart')?.destroy();
  }
}
```

---

## Notas Importantes

1. **Precios**: El endpoint asegura que los precios se obtengan correctamente del servicio relacionado. Si un servicio no tiene precio, se mostrará como 0.00.

2. **Filtrado por Rol**:
   - **Empleado/Recepcionista**: Solo verá sus propias citas
   - **Propietario/Admin**: Verá todas las citas del negocio

3. **Agrupación**: 
   - `dia`: Agrupa por día (YYYY-MM-DD)
   - `mes`: Agrupa por mes (YYYY-MM)
   - `semana`: Agrupa por semana (YYYY-WW)

4. **Optimización**: El endpoint solo carga las relaciones necesarias para mejorar el rendimiento.

5. **Formato de Fechas**: Todas las fechas están en formato ISO 8601 (YYYY-MM-DD o YYYY-MM-DD HH:mm:ss).

