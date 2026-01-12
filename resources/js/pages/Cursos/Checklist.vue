<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type {Cursos, Docente, SharedData, BreadcrumbItem} from '@/types';

type ChecklistStatus = Record<string, string>;

type ChecklistCourse = Cursos & {
  docente?: Docente | null;
  checklist_status?: ChecklistStatus;
  sede_label?: string | null;
  review_state?: string | null;
  drive_url?: string | null;
};

interface ChecklistPageProps extends SharedData {
  cursos: ChecklistCourse[];
  sedes?: string[];
  sedesCatalog?: { id: number; nombre: string }[];
  areasCatalog?: { id: number; nombre: string }[];
  modalidadesCatalog?: { id: number; nombre: string; area_id?: number | null }[];
  tiposEvidencia?: { id: number; codigo: string; nombre: string }[];
  filters?: { sede?: string; area_id?: string | number; modalidad_id?: string | number };
}

const page = usePage<ChecklistPageProps>();
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }, { title: 'Checklist', href: '/cursos/checklist' }];

const cursos = computed(() => page.props.cursos ?? []);
const userRole = computed(() => page.props.auth?.user?.role ?? null);
const sedes = computed<string[]>(() => page.props.sedes ?? []);
const sedesCatalog = computed(() => page.props.sedesCatalog ?? []);
const areasCatalog = computed(() => page.props.areasCatalog ?? []);
const modalidadesCatalog = computed(() => page.props.modalidadesCatalog ?? []);
const tiposEvidencia = computed(() => page.props.tiposEvidencia ?? []);
const itemsChecklist = computed(() => {
  const order = ['acta', 'guia', 'presentacion', 'trabajo', 'registro', 'informe_final'];
  return [...tiposEvidencia.value].sort((a: any, b: any) => {
    const ia = order.indexOf(a.codigo);
    const ib = order.indexOf(b.codigo);
    if (ia !== -1 || ib !== -1) {
      return (ia === -1 ? 999 : ia) - (ib === -1 ? 999 : ib);
    }
    return String(a.nombre).localeCompare(String(b.nombre));
  });
});
const sedesOptions = computed(() => {
  const catalog = sedesCatalog.value.map((s: any) => s.nombre);
  const fromResults = sedes.value;
  const merged = [...catalog, ...fromResults].filter((s) => s && s.trim() !== '');
  return Array.from(new Set(merged));
});
const sedeFiltro = ref<string>(page.props.filters?.sede ?? '');
const areaFiltro = ref<string>(String(page.props.filters?.area_id ?? ''));
const modalidadFiltro = ref<string>(String(page.props.filters?.modalidad_id ?? ''));

const modalidadesFiltradas = computed(() => {
  if (!areaFiltro.value) return modalidadesCatalog.value;
  return modalidadesCatalog.value.filter(
    (m) => String(m.area_id ?? '') === String(areaFiltro.value),
  );
});

watch(
  [sedeFiltro, areaFiltro, modalidadFiltro],
  () => {
    router.get(
      '/cursos/checklist',
      {
        sede: sedeFiltro.value || undefined,
        area_id: areaFiltro.value || undefined,
        modalidad_id: modalidadFiltro.value || undefined,
      },
      { preserveScroll: true, preserveState: true },
    );
  },
);

const labelFor = (value?: string | null) => {
  if (value === 'cumplido') return 'Cumplido';
  if (value === 'pendiente') return 'Pendiente';
  return 'N/A';
};

const canChangeState = computed(
  () => userRole.value === 'admin' || userRole.value === 'responsable',
);

const manualValue = (curso: ChecklistCourse, item: string) => {
  const manual = (curso as any).checklist_manual ?? {};
  return manual[item] ?? 'auto';
};

const updateManual = (cursoId: number, item: string, estado: 'auto' | 'cumplido' | 'pendiente') => {
  router.patch(
    `/cursos/${cursoId}/checklist-item`,
    { item, estado },
    { preserveScroll: true },
  );
};

const allItemsCumplidos = (status?: ChecklistStatus) => {
  if (!status) return false;
  if (!itemsChecklist.value.length) return false;
  return itemsChecklist.value.every((item) => (status as any)[item.codigo] === 'cumplido');
};

const changeState = (cursoId: number, action: 'pendiente' | 'observado' | 'validado') => {
  router.patch(
    `/cursos/${cursoId}/review-state`,
    { action },
    { preserveScroll: true },
  );
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
<Head title="Checklist de Cursos" />
    <div class="p-6">
      <h1 class="mb-2 text-2xl font-semibold">Checklist de cursos</h1>
      <p class="mb-4 text-sm text-muted-foreground">
        El estado de cada curso se calcula en base a evidencias validadas por el responsable o admin, además de actas
        y documentos finales registrados en el sistema.
      </p>

      <div class="mb-4 flex flex-wrap gap-3 items-center">
        <label class="text-sm flex items-center gap-2">
          <span class="text-muted-foreground">Area:</span>
          <select
            v-model="areaFiltro"
            class="border border-border bg-background text-foreground text-sm rounded px-2 py-1"
          >
            <option value="">Todas</option>
            <option v-for="a in areasCatalog" :key="a.id" :value="a.id">
              {{ a.nombre }}
            </option>
          </select>
        </label>
        <label class="text-sm flex items-center gap-2">
          <span class="text-muted-foreground">Modalidad:</span>
          <select
            v-model="modalidadFiltro"
            class="border border-border bg-background text-foreground text-sm rounded px-2 py-1"
          >
            <option value="">Todas</option>
            <option v-if="!modalidadesFiltradas.length" value="" disabled>Sin modalidades</option>
            <option v-for="m in modalidadesFiltradas" :key="m.id" :value="m.id">
              {{ m.nombre }}
            </option>
          </select>
        </label>
        <label class="text-sm flex items-center gap-2">
          <span class="text-muted-foreground">Sede:</span>
          <select
            v-model="sedeFiltro"
            class="border border-border bg-background text-foreground text-sm rounded px-2 py-1"
          >
            <option value="">Todas</option>
            <option v-for="sede in sedesOptions" :key="sede" :value="sede">
              {{ sede }}
            </option>
          </select>
        </label>
      </div>

      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Curso</TableHead>
            <TableHead>Docente</TableHead>
            <TableHead>Responsable</TableHead>
            <TableHead>Sede</TableHead>
            <TableHead v-for="item in itemsChecklist" :key="item.codigo">
              {{ item.nombre }}
            </TableHead>
            <TableHead>Avance</TableHead>
            <TableHead>Acciones</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="cursos.length === 0">
            <TableCell :colspan="6 + itemsChecklist.length" class="text-center text-muted-foreground">
              No hay cursos asignados por el momento.
            </TableCell>
          </TableRow>
          <TableRow v-for="curso in cursos" :key="curso.id">
            <TableCell class="font-medium">{{ curso.nombre }}</TableCell>
            <TableCell>
              <span v-if="curso.docente">
                {{ curso.docente.nombre }} {{ curso.docente.apellido }}
              </span>
              <span v-else class="text-muted-foreground">Sin docente asignado</span>
            </TableCell>
            <TableCell>{{ curso.responsable?.name ?? 'Sin asignar' }}</TableCell>
            <TableCell>{{ curso.sede_label ?? 'General' }}</TableCell>
            <TableCell v-for="item in itemsChecklist" :key="`${curso.id}-${item.codigo}`">
              <span :class="curso.checklist_status?.[item.codigo] === 'cumplido' ? 'text-emerald-600' : 'text-amber-600'">
                {{ labelFor(curso.checklist_status?.[item.codigo]) }}
              </span>
              <select
                v-if="canChangeState"
                class="ml-2 border border-border bg-background text-foreground text-xs rounded px-1 py-0.5"
                :value="manualValue(curso, item.codigo)"
                @change="updateManual(curso.id, item.codigo, ($event.target as HTMLSelectElement).value as any)"
              >
                <option value="auto">Auto</option>
                <option value="cumplido">Cumplido</option>
                <option value="pendiente">Pendiente</option>
              </select>
            </TableCell>
            <TableCell>
              <span>{{ curso.avance ?? 0 }}%</span>
            </TableCell>
            <TableCell>
              <div class="flex flex-col gap-2">
                <div class="text-xs">
                  Estado revisión:
                  <span class="font-medium">
                    {{ curso.review_state ?? 'pendiente' }}
                  </span>
                </div>
                <div v-if="canChangeState" class="flex flex-wrap gap-2">
                  <button
                    type="button"
                    class="px-2 py-1 text-xs rounded border border-amber-500 text-amber-700 hover:bg-amber-50"
                    @click="changeState(curso.id, 'observado')"
                  >
                    Observar
                  </button>
                  <button
                    type="button"
                    class="px-2 py-1 text-xs rounded border"
                    :class="allItemsCumplidos(curso.checklist_status) && curso.review_state !== 'validado'
                      ? 'border-emerald-600 text-emerald-700 hover:bg-emerald-50'
                      : 'border-muted text-muted-foreground cursor-not-allowed'"
                    :disabled="!allItemsCumplidos(curso.checklist_status) || curso.review_state === 'validado'"
                    @click="allItemsCumplidos(curso.checklist_status) && changeState(curso.id, 'validado')"
                  >
                    Validar
                  </button>
                </div>
                <a
                  v-if="curso.drive_url"
                  :href="curso.drive_url"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="text-primary hover:underline text-xs"
                >
                  Revisar curso
                </a>
                <span v-else class="text-xs text-muted-foreground">Sin Drive asignado</span>
              </div>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>
  </AppLayout>
</template>
