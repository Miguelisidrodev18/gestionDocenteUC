<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

type SummaryItem = {
  aprobados: number;
  desaprobados: number;
  total: number;
  porcentaje_aprobados: number;
  promedio: number;
  avance_promedio: number;
};

type FinalResult = {
  id: number;
  curso_id: number;
  sede: string;
  aprobados: number;
  desaprobados: number;
  promedio: number;
  avance_promedio: number;
  periodo: string;
  curso?: { id: number; nombre: string; codigo: string };
};

type Opportunity = {
  id: number;
  curso_id: number;
  sede: string;
  descripcion: string;
  owner_user_id?: number | null;
  due_date?: string | null;
  status: 'ABIERTA' | 'EN_PROGRESO' | 'CERRADA';
  curso?: { id: number; nombre: string; codigo: string };
  owner?: { id: number; name: string } | null;
};

type CourseOption = { id: number; nombre: string; codigo: string };

interface FinalPageProps {
  periodo: string;
  sede?: string | null;
  sedes: string[];
  summary: Record<string, SummaryItem>;
  results: FinalResult[];
  opportunities: Opportunity[];
  courses: CourseOption[];
}

const page = usePage<FinalPageProps>();
const periodo = ref<string>(page.props.periodo ?? '2025-2');
const sedeFiltro = ref<string>(page.props.sede ?? '');
const sedes = computed(() => page.props.sedes ?? []);
const summary = computed(() => page.props.summary ?? {});
const opportunities = computed(() => page.props.opportunities ?? []);
const courses = computed(() => page.props.courses ?? []);

watch([periodo, sedeFiltro], ([p, s]) => {
  router.get(
    '/final',
    { periodo: p, sede: s || undefined },
    { preserveScroll: true, preserveState: true },
  );
});

const nuevaOportunidad = ref<{
  curso_id: string;
  sede: string;
  descripcion: string;
  owner_user_id: string;
  due_date: string;
}>({
  curso_id: '',
  sede: '',
  descripcion: '',
  owner_user_id: '',
  due_date: '',
});

const estados = ['ABIERTA', 'EN_PROGRESO', 'CERRADA'] as const;

const crearOportunidad = () => {
  if (!nuevaOportunidad.value.curso_id || !nuevaOportunidad.value.descripcion) return;
  router.post(
    '/final/opportunities',
    {
      ...nuevaOportunidad.value,
      owner_user_id: nuevaOportunidad.value.owner_user_id || undefined,
      due_date: nuevaOportunidad.value.due_date || undefined,
    },
    {
      onSuccess: () => {
        nuevaOportunidad.value = {
          curso_id: '',
          sede: '',
          descripcion: '',
          owner_user_id: '',
          due_date: '',
        };
      },
    },
  );
};

const actualizarOportunidad = (op: Opportunity, patch: Partial<Opportunity>) => {
  router.put(
    `/final/opportunities/${op.id}`,
    {
      descripcion: patch.descripcion ?? op.descripcion,
      owner_user_id: patch.owner_user_id ?? op.owner_user_id ?? undefined,
      due_date: patch.due_date ?? op.due_date ?? undefined,
      status: patch.status ?? op.status,
    },
    { preserveScroll: true },
  );
};

const eliminarOportunidad = (op: Opportunity) => {
  if (!confirm('¿Eliminar esta oportunidad de mejora?')) return;
  router.delete(`/final/opportunities/${op.id}`, { preserveScroll: true });
};
</script>

<template>
  <AppLayout>
    <Head title="Resultados finales por sede" />
    <div class="p-6 space-y-6">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 class="text-2xl font-semibold">Resultados finales por sede</h1>
          <p class="text-sm text-muted-foreground">
            Resumen de aprobación y oportunidades de mejora agregadas por sede y asignatura.
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <label class="text-sm flex items-center gap-2">
            <span class="text-muted-foreground">Periodo:</span>
            <select v-model="periodo" class="border rounded px-2 py-1 text-sm bg-background text-foreground">
              <option value="2025-2">2025-2</option>
              <option value="2026-0">2026-0</option>
              <option value="2026-1">2026-1</option>
            </select>
          </label>
          <label class="text-sm flex items-center gap-2">
            <span class="text-muted-foreground">Sede:</span>
            <select v-model="sedeFiltro" class="border rounded px-2 py-1 text-sm bg-background text-foreground">
              <option value="">Todas</option>
              <option v-for="sede in sedes" :key="sede" :value="sede">
                {{ sede }}
              </option>
            </select>
          </label>
        </div>
      </div>

      <!-- Tabla de KPIs por sede -->
      <div class="rounded-lg border bg-card">
        <div class="px-4 py-2 border-b text-sm font-medium">Indicadores por sede</div>
        <table class="w-full text-sm">
          <thead class="bg-muted">
            <tr>
              <th class="text-left px-4 py-2">Sede</th>
              <th class="text-right px-4 py-2">Aprobados</th>
              <th class="text-right px-4 py-2">Desaprobados</th>
              <th class="text-right px-4 py-2">% Aprobados</th>
              <th class="text-right px-4 py-2">Promedio EP</th>
              <th class="text-right px-4 py-2">Avance promedio (%)</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="Object.keys(summary).length === 0">
              <td colspan="6" class="px-4 py-3 text-center text-muted-foreground">
                No hay datos consolidados para el periodo/sede seleccionados.
              </td>
            </tr>
            <tr v-for="(row, sede) in summary" :key="sede" class="border-t">
              <td class="px-4 py-2 font-medium">{{ sede }}</td>
              <td class="px-4 py-2 text-right">{{ row.aprobados }}</td>
              <td class="px-4 py-2 text-right">{{ row.desaprobados }}</td>
              <td class="px-4 py-2 text-right">{{ row.porcentaje_aprobados }}%</td>
              <td class="px-4 py-2 text-right">{{ row.promedio }}</td>
              <td class="px-4 py-2 text-right">{{ row.avance_promedio }}%</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Formulario para nueva oportunidad -->
      <div class="rounded-lg border bg-card p-4 space-y-3">
        <h2 class="text-sm font-semibold">Nueva oportunidad de mejora</h2>
        <div class="grid gap-2 md:grid-cols-4">
          <select
            v-model="nuevaOportunidad.curso_id"
            class="border rounded px-2 py-1 text-sm bg-background text-foreground"
          >
            <option value="">Seleccione curso</option>
            <option v-for="c in courses" :key="c.id" :value="c.id">
              {{ c.codigo }} - {{ c.nombre }}
            </option>
          </select>
          <input
            v-model="nuevaOportunidad.sede"
            class="border rounded px-2 py-1 text-sm bg-background text-foreground"
            placeholder="Sede (ej. Huancayo)"
          />
          <input
            v-model="nuevaOportunidad.owner_user_id"
            class="border rounded px-2 py-1 text-sm bg-background text-foreground"
            placeholder="ID responsable (opcional)"
          />
          <input
            v-model="nuevaOportunidad.due_date"
            type="date"
            class="border rounded px-2 py-1 text-sm bg-background text-foreground"
          />
        </div>
        <textarea
          v-model="nuevaOportunidad.descripcion"
          class="w-full border rounded px-2 py-1 text-sm bg-background text-foreground"
          rows="3"
          placeholder="Describe la oportunidad de mejora..."
        />
        <div class="flex justify-end">
          <button
            type="button"
            class="px-3 py-1 rounded bg-primary text-primary-foreground text-sm hover:opacity-90"
            @click="crearOportunidad"
          >
            Guardar oportunidad
          </button>
        </div>
      </div>

      <!-- Cards de oportunidades -->
      <div class="space-y-3">
        <h2 class="text-sm font-semibold">Oportunidades registradas</h2>
        <div v-if="opportunities.length === 0" class="text-sm text-muted-foreground">
          No hay oportunidades registradas para el filtro actual.
        </div>
        <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="op in opportunities"
            :key="op.id"
            class="border rounded-lg bg-card p-3 flex flex-col justify-between"
          >
            <div class="space-y-1">
              <div class="text-xs text-muted-foreground flex justify-between">
                <span>Sede: <span class="font-medium text-foreground">{{ op.sede }}</span></span>
                <span>Curso: {{ op.curso?.codigo ?? op.curso_id }}</span>
              </div>
              <div class="text-sm font-medium line-clamp-3">{{ op.descripcion }}</div>
              <div class="text-xs text-muted-foreground">
                Responsable:
                <span class="font-medium text-foreground">
                  {{ op.owner?.name ?? 'No asignado' }}
                </span>
              </div>
              <div class="text-xs text-muted-foreground">
                Vence:
                <span class="font-medium text-foreground">
                  {{ op.due_date ?? 'Sin fecha' }}
                </span>
              </div>
            </div>
            <div class="mt-3 flex items-center justify-between text-xs">
              <div class="flex gap-1">
                <span class="px-2 py-0.5 rounded-full border" :class="{
                  'border-emerald-500 text-emerald-700': op.status === 'CERRADA',
                  'border-amber-500 text-amber-700': op.status === 'EN_PROGRESO',
                  'border-slate-400 text-slate-700': op.status === 'ABIERTA',
                }">
                  {{ op.status }}
                </span>
              </div>
              <div class="flex gap-1">
                <select
                  class="border rounded px-1 py-0.5 text-xs bg-background text-foreground"
                  :value="op.status"
                  @change="(e:any) => actualizarOportunidad(op, { status: e.target.value as any })"
                >
                  <option v-for="st in estados" :key="st" :value="st">{{ st }}</option>
                </select>
                <button
                  type="button"
                  class="px-2 py-0.5 rounded border border-destructive text-destructive hover:bg-destructive/10"
                  @click="eliminarOportunidad(op)"
                >
                  X
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

