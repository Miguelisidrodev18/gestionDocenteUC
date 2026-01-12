<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

const { props } = usePage() as any;
const role = props.currentUserRole ?? props.auth?.user?.role;
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
];

// Datos generales
const docentesCount = props.docentesCount ?? 0;
const cursosCount = props.cursosCount ?? 0;
const cursosPorPeriodo = props.cursosPorPeriodo ?? [];
const cursosDocente = props.cursosDocente ?? [];
const periodosCatalog = props.periodosCatalog ?? [];
const sedesCatalog = props.sedesCatalog ?? [];
const areasCatalog = props.areasCatalog ?? [];
const modalidadesCatalog = props.modalidadesCatalog ?? [];

// Métricas avanzadas (admin / responsable)
const filters = ref({
  periodo: props.initialFilters?.periodo ?? null,
  sede: props.initialFilters?.sede ?? null,
  area_id: props.initialFilters?.area_id ?? null,
  modalidad_id: props.initialFilters?.modalidad_id ?? null,
});

const metrics = ref(props.initialMetrics ?? null);
const loadingMetrics = ref(false);

// Círculo de progreso (avance promedio)
const circleRadius = 60;
const circleCircumference = 2 * Math.PI * circleRadius;

const riskPercent = computed(() => {
  const resumen = metrics.value?.resumen;
  if (!resumen) return 0;
  const total = Number(resumen.total_cursos ?? 0);
  const riesgo = Number(resumen.cursos_riesgo ?? 0);
  if (!total) return 0;
  return Math.round((riesgo / total) * 100);
});

// Serie mensual: puntos para gráfico de línea SVG
const monthlyPolyline = computed(() => {
  const series = (metrics.value?.series_mensuales as any[]) ?? [];
  if (!series.length) return '';
  const max = Math.max(...series.map((p) => Number(p.ep_promedio ?? 0)));
  if (!max) return '';

  const lastIndex = Math.max(series.length - 1, 1);

  return series
    .map((p, index) => {
      const x = (index / lastIndex) * 100;
      const y = 10 + (1 - Number(p.ep_promedio ?? 0) / max) * 70; // entre 10 y 80
      return `${x},${y}`;
    })
    .join(' ');
});

const periodosOptions = computed(() => {
  if (periodosCatalog.length) {
    return periodosCatalog.map((p: any) => p.codigo);
  }
  return (cursosPorPeriodo as any[]).map((p) => p.periodo);
});

const modalidadesFiltradas = computed(() => {
  if (!filters.value.area_id) return modalidadesCatalog;
  return (modalidadesCatalog as any[]).filter(
    (m) => String(m.area_id ?? '') === String(filters.value.area_id),
  );
});

const selectedPeriodo = computed(() => {
  return filters.value.periodo ?? (periodosOptions.value[0] ?? null);
});

const selectedPeriodoTotal = computed(() => {
  const periodo = selectedPeriodo.value;
  if (!periodo) return 0;
  const match = (cursosPorPeriodo as any[]).find((p) => p.periodo === periodo);
  return match?.total ?? 0;
});

watch(
  () => filters.value.area_id,
  () => {
    filters.value.modalidad_id = null;
  },
);

onMounted(() => {
  if (!metrics.value && role !== 'docente') {
    loadMetrics();
  }
});

function loadMetrics() {
  if (role === 'docente') return;
  loadingMetrics.value = true;

  const params = new URLSearchParams();
  if (filters.value.periodo) params.append('periodo', filters.value.periodo);
  if (filters.value.sede) params.append('sede', filters.value.sede);
  if (filters.value.area_id) params.append('area_id', String(filters.value.area_id));
  if (filters.value.modalidad_id) params.append('modalidad_id', String(filters.value.modalidad_id));

  fetch(`/dashboard/metrics?${params.toString()}`, {
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
  })
    .then((r) => r.json())
    .then((data) => {
      metrics.value = data;
    })
    .finally(() => {
      loadingMetrics.value = false;
    });
}

function goToCursos() {
  router.get('/cursos');
}

function goToDocentes() {
  router.get('/docents');
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="p-8 min-h-screen text-foreground bg-gradient-to-b from-[#FBE4D8] via-[#854F6C] to-[#190019]"
    >
      <div class="mb-8 text-center">
        <h1 class="text-4xl font-extrabold tracking-tight text-primary-foreground drop-shadow">
          Panel general
        </h1>
        <p class="mt-2 text-sm text-primary-foreground/80">
          Seguimiento de avance por area, modalidad y sede.
        </p>
      </div>

      <!-- Vista DOCENTE -->
      <div v-if="role === 'docente'">
        <h2 class="text-xl font-semibold mb-4">Mis cursos</h2>
        <div v-if="!cursosDocente.length" class="text-sm text-muted-foreground">
          No tienes cursos asignados.
        </div>
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="c in cursosDocente"
            :key="c.id"
            class="bg-card/90 backdrop-blur rounded-2xl border border-white/10 shadow-lg shadow-[#190019]/30 p-4"
          >
            <div class="font-semibold mb-1">{{ c.codigo }} - {{ c.nombre }}</div>
            <div class="text-xs text-muted-foreground mb-2">
              Área: {{ c.area ?? '—' }} · Modalidad: {{ c.modalidad }}
            </div>
            <div class="text-xs mb-1">Avance: {{ c.avance }}%</div>
            <div class="h-2 bg-muted rounded mb-3">
              <div class="h-2 bg-green-600 rounded" :style="{ width: `${c.avance}%` }" />
            </div>
            <div class="text-xs mb-2 space-x-2">
              <span>Actas: {{ c.faltantes?.acta ?? 0 }}</span>
              <span>Guías: {{ c.faltantes?.guia ?? 0 }}</span>
              <span>Present.: {{ c.faltantes?.presentacion ?? 0 }}</span>
              <span>Trabajos: {{ c.faltantes?.trabajo ?? 0 }}</span>
            </div>
            <div class="flex gap-2 justify-end">
              <button class="p-1" title="Abrir" @click="$inertia.get(`/cursos/${c.id}`)">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 24 24"
                  fill="currentColor"
                  class="w-5 h-5 text-primary"
                >
                  <path
                    d="M12 5C5 5 2 12 2 12s3 7 10 7 10-7 10-7-3-7-10-7Zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10Z"
                  />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Vista ADMIN / RESPONSABLE -->
      <div v-else class="space-y-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
          <!-- Número de docentes -->
          <div
            class="bg-card/90 backdrop-blur p-6 rounded-2xl shadow-lg shadow-[#190019]/30 border border-white/10"
          >
            <h2 class="text-xl font-semibold mb-2">Docentes registrados</h2>
            <p class="text-3xl font-bold text-green-300">{{ docentesCount }}</p>
          </div>

          <!-- Número de cursos -->
          <div
            class="bg-card/90 backdrop-blur p-6 rounded-2xl shadow-lg shadow-[#190019]/30 border border-white/10"
          >
            <h2 class="text-xl font-semibold mb-2">Cursos registrados</h2>
            <p class="text-3xl font-bold text-purple-300">{{ cursosCount }}</p>
          </div>

          <!-- Cursos por periodo -->
          <div
            class="bg-card/90 backdrop-blur p-6 rounded-2xl shadow-lg shadow-[#190019]/30 border border-white/10 flex flex-col justify-between"
          >
            <div>
              <h2 class="text-xl font-semibold mb-1">Cursos por periodo</h2>
              <p class="text-xs text-muted-foreground mb-4">
                Periodo seleccionado: <span class="text-foreground font-medium">{{ selectedPeriodo }}</span>
              </p>
              <p class="text-3xl font-bold text-green-300">
                {{ selectedPeriodoTotal }}
              </p>
            </div>
            <div class="mt-4 text-[11px] text-muted-foreground">
              <span class="font-semibold">Resumen periodos:</span>
              <span v-for="p in cursosPorPeriodo" :key="p.periodo" class="ml-2">
                {{ p.periodo }}: <span class="text-foreground">{{ p.total }}</span>
              </span>
            </div>
          </div>
        </div>

        <!-- Filtros de métricas -->
        <div
          class="bg-card/90 backdrop-blur p-4 rounded-2xl border border-white/10 flex flex-wrap items-end gap-4 shadow-md shadow-[#190019]/30"
        >
          <div class="flex flex-col">
            <label class="text-xs text-muted-foreground mb-1">Periodo</label>
            <select
              v-model="filters.periodo"
              class="border border-border bg-background text-foreground p-2 rounded min-w-[140px]"
            >
              <option v-for="p in periodosOptions" :key="p" :value="p">
                {{ p }}
              </option>
            </select>
          </div>

          <div class="flex flex-col">
            <label class="text-xs text-muted-foreground mb-1">Area</label>
            <select
              v-model="filters.area_id"
              class="border border-border bg-background text-foreground p-2 rounded min-w-[160px]"
            >
              <option value="">Todas</option>
              <option v-for="a in areasCatalog" :key="a.id" :value="a.id">
                {{ a.nombre }}
              </option>
            </select>
          </div>

          <div class="flex flex-col">
            <label class="text-xs text-muted-foreground mb-1">Modalidad</label>
            <select
              v-model="filters.modalidad_id"
              class="border border-border bg-background text-foreground p-2 rounded min-w-[180px]"
            >
              <option value="">Todas</option>
              <option v-if="!modalidadesFiltradas.length" value="" disabled>Sin modalidades</option>
              <option v-for="m in modalidadesFiltradas" :key="m.id" :value="m.id">
                {{ m.nombre }}
              </option>
            </select>
          </div>

          <div class="flex flex-col">
            <label class="text-xs text-muted-foreground mb-1">Sede</label>
            <select
              v-if="sedesCatalog.length"
              v-model="filters.sede"
              class="border border-border bg-background text-foreground p-2 rounded min-w-[160px]"
            >
              <option value="">Todos</option>
              <option v-for="s in sedesCatalog" :key="s.id" :value="s.nombre">
                {{ s.nombre }}
              </option>
            </select>
            <input
              v-else
              v-model="filters.sede"
              placeholder="Sede"
              class="border border-border bg-background text-foreground p-2 rounded min-w-[160px]"
            />
          </div>

          <button
            type="button"
            class="px-4 py-2 rounded bg-primary text-primary-foreground hover:opacity-90"
            :disabled="loadingMetrics"
            @click="loadMetrics"
          >
            {{ loadingMetrics ? 'Actualizando…' : 'Aplicar filtros' }}
          </button>
        </div>

        <!-- KPIs y gráficos -->
        <div v-if="metrics" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- KPIs principales con círculo -->
          <div class="space-y-4">
            <div
              class="bg-card/90 backdrop-blur p-4 rounded-2xl border border-white/10 shadow-md shadow-[#190019]/30 flex flex-col items-center justify-center gap-4"
            >
              <div class="w-40 h-40 relative">
                <svg viewBox="0 0 160 160" class="w-full h-full">
                  <circle
                    :r="circleRadius"
                    cx="80"
                    cy="80"
                    class="stroke-muted"
                    :stroke-width="14"
                    fill="none"
                    :stroke-dasharray="circleCircumference"
                  />
                  <circle
                    :r="circleRadius"
                    cx="80"
                    cy="80"
                    class="text-primary"
                    :stroke-width="14"
                    fill="none"
                    stroke-linecap="round"
                    :stroke-dasharray="circleCircumference"
                    :stroke-dashoffset="
                      circleCircumference *
                      (1 - (Number(metrics.resumen?.avance_promedio ?? 0) / 100))
                    "
                    stroke="currentColor"
                  />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                  <div class="text-xs text-muted-foreground">Avance promedio</div>
                  <div class="text-3xl font-bold">
                    {{ metrics.resumen?.avance_promedio ?? 0 }}%
                  </div>
                  <div class="text-[11px] text-muted-foreground">
                    {{ metrics.sede ?? 'Todas las sedes' }} · {{ metrics.periodo }}
                  </div>
                </div>
              </div>

              <div class="flex gap-6 text-xs mt-2">
                <div class="flex flex-col items-center">
                  <span class="text-muted-foreground mb-1">Total cursos</span>
                  <span class="px-3 py-1 rounded-full bg-secondary/40 text-sm font-semibold">
                    {{ metrics.resumen?.total_cursos ?? 0 }}
                  </span>
                </div>
                <div class="flex flex-col items-center">
                  <span class="text-muted-foreground mb-1">En riesgo (&lt; 60%)</span>
                  <span class="px-3 py-1 rounded-full bg-destructive/20 text-sm font-semibold text-red-300">
                    {{ riskPercent }}%
                  </span>
                </div>
              </div>
            </div>

            <div
              class="bg-card/90 backdrop-blur p-4 rounded-2xl border border-white/10 shadow-md shadow-[#190019]/30"
            >
              <h3 class="text-sm font-semibold mb-2">Top 5 con menor avance</h3>
              <div v-if="!metrics.top_riesgo?.length" class="text-xs text-muted-foreground">
                No hay cursos para este filtro.
              </div>
              <ul v-else class="space-y-2 text-xs max-h-64 overflow-auto">
                <li v-for="c in metrics.top_riesgo" :key="c.id" class="flex justify-between items-center">
                  <div class="mr-2 truncate">
                    <div class="font-medium truncate">{{ c.codigo }} - {{ c.nombre }}</div>
                    <div class="text-[11px] text-muted-foreground truncate">
                      {{ c.responsable ?? 'Sin responsable' }}
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="font-semibold text-red-400">{{ c.avance }}%</div>
                  </div>
                </li>
              </ul>
            </div>
          </div>

          <!-- Avance por responsable (barras) -->
          <div
            class="bg-card/90 backdrop-blur p-4 rounded-2xl border border-white/10 shadow-md shadow-[#190019]/30 lg:col-span-1"
          >
            <h3 class="text-sm font-semibold mb-2">Avance por responsable</h3>
            <div v-if="!metrics.por_responsable?.length" class="text-xs text-muted-foreground">
              No hay datos de responsables para este filtro.
            </div>
            <div v-else class="space-y-2 max-h-80 overflow-auto">
              <div
                v-for="r in metrics.por_responsable"
                :key="r.responsable_id"
                class="text-xs space-y-1"
              >
                <div class="flex justify-between">
                  <span class="truncate mr-2">{{ r.responsable }}</span>
                  <span class="text-muted-foreground">{{ r.cursos }} cursos</span>
                </div>
                <div class="h-2 bg-muted rounded">
                  <div
                    class="h-2 rounded bg-purple-400"
                    :style="{ width: `${r.avance_promedio ?? 0}%` }"
                  />
                </div>
                <div class="text-[11px] text-muted-foreground">
                  Avance medio: {{ r.avance_promedio ?? 0 }}%
                </div>
              </div>
            </div>
          </div>

          <!-- Serie mensual EP promedio (gráfico de línea SVG) -->
          <div
            class="bg-card/90 backdrop-blur p-4 rounded-2xl border border-white/10 shadow-md shadow-[#190019]/30 lg:col-span-1"
          >
            <h3 class="text-sm font-semibold mb-2">Serie mensual (EP promedio)</h3>
            <div v-if="!metrics.series_mensuales?.length" class="text-xs text-muted-foreground">
              Aún no hay registros de notas para este periodo/filtro.
            </div>
            <div v-else class="space-y-3 text-xs">
              <div class="w-full h-40 relative">
                <svg viewBox="0 0 100 100" class="w-full h-full">
                  <defs>
                    <linearGradient id="lineGradient" x1="0" y1="0" x2="1" y2="0">
                      <stop offset="0%" stop-color="#FBE4D8" />
                      <stop offset="50%" stop-color="#DFB6B2" />
                      <stop offset="100%" stop-color="#854F6C" />
                    </linearGradient>
                  </defs>
                  <!-- Fondo -->
                  <rect x="0" y="10" width="100" height="80" rx="6" class="fill-muted/40" />
                  <!-- Ejes X e Y -->
                  <line x1="8" y1="80" x2="92" y2="80" class="stroke-muted-foreground" stroke-width="0.5" />
                  <line x1="8" y1="80" x2="8" y2="20" class="stroke-muted-foreground" stroke-width="0.5" />
                  <polyline
                    :points="monthlyPolyline"
                    fill="none"
                    stroke="url(#lineGradient)"
                    stroke-width="2"
                  />
                  <!-- Etiquetas de ejes -->
                  <text x="50" y="94" text-anchor="middle" class="fill-muted-foreground" font-size="6">
                    Mes
                  </text>
                  <text
                    x="4"
                    y="50"
                    text-anchor="middle"
                    transform="rotate(-90 4,50)"
                    class="fill-muted-foreground"
                    font-size="6"
                  >
                    Promedio EP
                  </text>
                </svg>
              </div>
              <div class="flex flex-wrap gap-2 text-[11px] text-muted-foreground">
                <span v-for="p in metrics.series_mensuales" :key="p.mes">
                  {{ p.mes }}:
                  <span class="text-foreground">{{ p.ep_promedio }}</span>
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Acceso rápido -->
        <div
          class="bg-card/90 backdrop-blur p-6 rounded-2xl shadow-lg shadow-[#190019]/30 border border-white/10"
        >
          <h2 class="text-xl font-semibold mb-2">Acceso rápido</h2>
          <div class="flex flex-wrap gap-4">
            <button
              @click="goToDocentes"
              class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition"
            >
              Ver docentes
            </button>
            <button
              @click="goToCursos"
              class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition"
            >
              Ver cursos
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
