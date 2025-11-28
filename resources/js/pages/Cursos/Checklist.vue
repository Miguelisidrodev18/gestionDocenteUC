<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { Cursos, Docente, SharedData } from '@/types';

type ChecklistStatus = {
  actas?: string;
  guias?: string;
  presentaciones?: string;
  trabajos?: string;
  finales?: string;
};

type ChecklistCourse = Cursos & {
  docente?: Docente | null;
  checklist_status?: ChecklistStatus;
  sede_label?: string | null;
  review_state?: string | null;
};

interface ChecklistPageProps extends SharedData {
  cursos: ChecklistCourse[];
  sedes?: string[];
  filters?: { sede?: string };
}

const page = usePage<ChecklistPageProps>();
const cursos = computed(() => page.props.cursos ?? []);
const userRole = computed(() => page.props.auth?.user?.role ?? null);
const sedes = computed<string[]>(() => page.props.sedes ?? []);
const sedeFiltro = ref<string>(page.props.filters?.sede ?? '');

watch(
  sedeFiltro,
  (value) => {
    router.get(
      '/cursos/checklist',
      { sede: value || undefined },
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

const allItemsCumplidos = (status?: ChecklistStatus) => {
  if (!status) return false;
  return ['actas', 'guias', 'presentaciones', 'trabajos', 'finales'].every(
    (k) => (status as any)[k] === 'cumplido',
  );
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
  <AppLayout>
    <Head title="Checklist de Cursos" />
    <div class="p-6">
      <h1 class="mb-2 text-2xl font-semibold">Checklist de cursos</h1>
      <p class="mb-4 text-sm text-muted-foreground">
        El estado de cada curso se calcula automáticamente a partir de actas, guías, presentaciones, trabajos y
        documentos finales registrados en el sistema.
      </p>

      <div class="mb-4 flex flex-wrap gap-3 items-center">
        <label class="text-sm flex items-center gap-2">
          <span class="text-muted-foreground">Sede:</span>
          <select
            v-model="sedeFiltro"
            class="border border-border bg-background text-foreground text-sm rounded px-2 py-1"
          >
            <option value="">Todas</option>
            <option v-for="sede in sedes" :key="sede" :value="sede">
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
            <TableHead>Actas</TableHead>
            <TableHead>Guías</TableHead>
            <TableHead>Presentaciones</TableHead>
            <TableHead>Trabajos</TableHead>
            <TableHead>Docs. finales</TableHead>
            <TableHead>Avance</TableHead>
            <TableHead>Acciones</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="cursos.length === 0">
            <TableCell colspan="11" class="text-center text-muted-foreground">
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
            <TableCell>
              <span :class="curso.checklist_status?.actas === 'cumplido' ? 'text-emerald-600' : 'text-amber-600'">
                {{ labelFor(curso.checklist_status?.actas) }}
              </span>
            </TableCell>
            <TableCell>
              <span :class="curso.checklist_status?.guias === 'cumplido' ? 'text-emerald-600' : 'text-amber-600'">
                {{ labelFor(curso.checklist_status?.guias) }}
              </span>
            </TableCell>
            <TableCell>
              <span :class="curso.checklist_status?.presentaciones === 'cumplido' ? 'text-emerald-600' : 'text-amber-600'">
                {{ labelFor(curso.checklist_status?.presentaciones) }}
              </span>
            </TableCell>
            <TableCell>
              <span :class="curso.checklist_status?.trabajos === 'cumplido' ? 'text-emerald-600' : 'text-amber-600'">
                {{ labelFor(curso.checklist_status?.trabajos) }}
              </span>
            </TableCell>
            <TableCell>
              <span :class="curso.checklist_status?.finales === 'cumplido' ? 'text-emerald-600' : 'text-amber-600'">
                {{ labelFor(curso.checklist_status?.finales) }}
              </span>
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
                <Link :href="`/cursos/${curso.id}/edit`" class="text-primary hover:underline text-xs">
                  Revisar curso
                </Link>
              </div>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>
  </AppLayout>
</template>

