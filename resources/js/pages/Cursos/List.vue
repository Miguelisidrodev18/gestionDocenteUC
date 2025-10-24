<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { ref, onMounted, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const page: any = usePage();
const docentes = page.props.docentes ?? [];
const responsables = page.props.responsables ?? [];
const cursosProp = page.props.cursos ?? [];
const areas = page.props.areas ?? [];
const currentUserRole = page.props.currentUserRole ?? page.props.auth?.user?.role;
const currentDocenteId = page.props.currentDocenteId ?? null;

const mostrarFormulario = ref(false);
const vista = ref<'tabla'|'tarjetas'>('tabla');
const search = ref(page.props.filters?.q ?? '');
const periodoSeleccionado = ref(page.props.periodo ?? '2025-2');
const filtroDocenteId = ref(page.props.filters?.docente_id ?? '');
const sort = ref(page.props.filters?.sort ?? 'nombre');
const dir = ref(page.props.filters?.dir ?? 'asc');
const perPage = ref(page.props.filters?.per_page ?? 12);
const soloMios = ref(!!page.props.filters?.mine);

const nuevoCurso = ref({
  nombre: '', codigo: '', descripcion: '', creditos: '', nivel: 'pregrado',
  modalidad: 'presencial', modalidad_id: '', docente_id: '', drive_url: '',
  responsable_id: '', periodo_academico: '', docentes_ids: [] as any[],
});

onMounted(() => {
  if (currentUserRole === 'docente' && currentDocenteId) {
    nuevoCurso.value.docente_id = String(currentDocenteId);
  }
});

const cursosList = computed(() => (cursosProp?.data ? cursosProp.data : cursosProp) || []);
const cursosLinks = computed(() => cursosProp?.links ?? []);
const cursosSinModalidad = computed(() => cursosList.value.filter((c:any) => !c.modalidad_id));

function verCurso(id: number) { router.get(`/cursos/${id}`); }
function editarCurso(id: number) { router.get(`/cursos/${id}/edit`); }
function abrirGoogleDrive(driveUrl: string) { if (driveUrl) window.open(driveUrl, '_blank'); }
function eliminarCurso(id: number) { if (confirm('¿Eliminar este curso?')) router.delete(`/cursos/${id}`, { onSuccess: () => router.reload() }); }

function aplicarFiltros(extra: Record<string, any> = {}) {
  const params: Record<string, any> = {
    periodo: periodoSeleccionado.value,
    q: search.value || undefined,
    sort: sort.value,
    dir: dir.value,
    per_page: perPage.value,
    mine: soloMios.value || undefined,
    ...extra,
  };
  if (currentUserRole === 'admin' && filtroDocenteId.value) params.docente_id = filtroDocenteId.value;
  router.get('/cursos', params, { preserveState: true, preserveScroll: true });
}
function ordenar(col: string) {
  if (sort.value === col) dir.value = dir.value === 'asc' ? 'desc' : 'asc';
  else { sort.value = col; dir.value = 'asc'; }
  aplicarFiltros({ page: 1 });
}
function cambiarPagina(url: string) { if (url) router.get(url, {}, { preserveState: true, preserveScroll: true }); }
function agregarCurso() {
  const payload: any = { ...nuevoCurso.value, periodo: periodoSeleccionado.value };
  if (!payload.responsable_id) delete payload.responsable_id;
  router.post('/cursos', payload, { onSuccess: () => { mostrarFormulario.value = false; router.reload(); } });
}
</script>

<template>
  <AppLayout>
    <div class="p-8 min-h-screen bg-background text-foreground">
      <div class="flex flex-wrap items-center gap-3 mb-6">
        <h1 class="text-2xl font-bold">Asignaturas</h1>
        <select v-model="periodoSeleccionado" @change="aplicarFiltros" class="border border-border bg-background text-foreground p-2 rounded">
          <option value="2025-2">2025-2</option>
          <option value="2026-0">2026-0</option>
          <option value="2026-1">2026-1</option>
        </select>
        <input v-model="search" @keyup.enter="aplicarFiltros({ page: 1 })" placeholder="Buscar código, nombre o docente" class="flex-1 min-w-[240px] border border-border bg-background text-foreground p-2 rounded" />
        <label v-if="currentUserRole === 'docente'" class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="soloMios" @change="aplicarFiltros({ page: 1 })"/> Solo mis cursos</label>
        <select v-if="currentUserRole === 'admin'" v-model="filtroDocenteId" @change="aplicarFiltros" class="border border-border bg-background text-foreground p-2 rounded">
          <option value="">Todos los docentes</option>
          <option v-for="docente in docentes" :key="docente.id" :value="docente.id">{{ docente.nombre }} {{ docente.apellido }}</option>
        </select>
        <select v-model.number="perPage" @change="aplicarFiltros({ page: 1 })" class="border border-border bg-background text-foreground p-2 rounded">
          <option :value="8">8</option>
          <option :value="12">12</option>
          <option :value="24">24</option>
        </select>
        <div class="ml-auto flex items-center gap-2">
          <button @click="vista = 'tabla'" :class="['px-3 py-1 rounded border', vista==='tabla' ? 'bg-primary text-primary-foreground' : '']">Tabla</button>
          <button @click="vista = 'tarjetas'" :class="['px-3 py-1 rounded border', vista==='tarjetas' ? 'bg-primary text-primary-foreground' : '']">Tarjetas</button>
          <button v-if="currentUserRole !== 'docente'" @click="mostrarFormulario = true" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Agregar curso</button>
        </div>
      </div>

      <!-- Modal crear curso -->
      <div v-if="mostrarFormulario" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <form @submit.prevent="agregarCurso" class="w-[90vw] max-w-3xl grid grid-cols-1 md:grid-cols-2 gap-4 p-6 rounded-xl border bg-card shadow-xl">
          <label class="flex flex-col gap-1">
            <span class="text-sm text-gray-600">Nombre del curso</span>
            <input v-model="nuevoCurso.nombre" class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring" required />
          </label>
          <label class="flex flex-col gap-1">
            <span class="text-sm text-gray-600">Código</span>
            <input v-model="nuevoCurso.codigo" class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring" required />
          </label>
          <label class="md:col-span-2 flex flex-col gap-1">
            <span class="text-sm text-gray-600">Descripción</span>
            <input v-model="nuevoCurso.descripcion" class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring" />
          </label>
          <label class="flex flex-col gap-1">
            <span class="text-sm text-gray-600">Créditos</span>
            <input v-model="nuevoCurso.creditos" type="number" class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring" required />
          </label>
          <label class="flex flex-col gap-1">
            <span class="text-sm text-gray-600">Nivel</span>
            <select v-model="nuevoCurso.nivel" class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring" required>
              <option value="pregrado">Pregrado</option>
              <option value="postgrado">Postgrado</option>
            </select>
          </label>
          <label class="flex flex-col gap-1">
            <span class="text-sm text-gray-600">Área y modalidad</span>
            <select v-model="nuevoCurso.modalidad_id" class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring">
              <option value="">Seleccione modalidad</option>
              <template v-for="a in areas" :key="a.id">
                <optgroup :label="a.nombre">
                  <option v-for="m in a.modalidades" :key="m.id" :value="m.id">{{ m.nombre }} ({{ m.duracion_semanas }} sem)</option>
                </optgroup>
              </template>
            </select>
          </label>
          <label class="flex flex-col gap-1">
            <span class="text-sm text-gray-600">Rama / Área</span>
            <input class="border border-border bg-background text-foreground p-2 rounded" :value="areas.find((a:any) => a.modalidades.some((m:any) => m.id === Number(nuevoCurso.modalidad_id)))?.nombre ?? 'Seleccione área/modalidad'" disabled />
          </label>
          <select v-if="currentUserRole !== 'docente'" v-model="nuevoCurso.docente_id" class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring" required>
            <option value="">Seleccione un docente</option>
            <option v-for="docente in docentes" :key="docente.id" :value="docente.id">{{ docente.nombre }} {{ docente.apellido }}</option>
          </select>
          <input v-else class="border border-border p-2 rounded bg-muted text-foreground" :value="docentes.find((d:any) => d.id === Number(nuevoCurso.docente_id))?.nombre ?? 'Mi usuario'" disabled />
          <label class="flex flex-col gap-1">
            <span class="text-sm text-gray-600">Responsable</span>
            <select v-model="nuevoCurso.responsable_id" class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring">
              <option value="">Seleccione responsable</option>
              <option v-for="responsable in responsables" :key="responsable.id" :value="responsable.id">{{ responsable.name }}</option>
            </select>
          </label>
          <label class="flex flex-col gap-1">
            <span class="text-sm text-gray-600">Docentes que dictan</span>
            <select v-model="nuevoCurso.docentes_ids" multiple class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring">
              <option v-for="docente in docentes" :key="docente.id" :value="docente.id">{{ docente.nombre }} {{ docente.apellido }}</option>
            </select>
          </label>
          <label class="md:col-span-2 flex flex-col gap-1">
            <span class="text-sm text-gray-600">URL de Google Drive</span>
            <input v-model="nuevoCurso.drive_url" class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring" />
          </label>
          <label class="md:col-span-2 flex flex-col gap-1">
            <span class="text-sm text-gray-600">Período Académico</span>
            <input v-model="nuevoCurso.periodo_academico" :placeholder="periodoSeleccionado" class="border border-border bg-background text-foreground p-2 rounded focus:outline-none focus:ring-2 focus:ring-ring" />
          </label>
          <div class="md:col-span-2 flex justify-end gap-2">
            <button type="button" @click="mostrarFormulario=false" class="px-4 py-2 rounded border">Cancelar</button>
            <button type="submit" class="bg-primary text-primary-foreground px-4 py-2 rounded hover:opacity-90 transition">Guardar</button>
          </div>
        </form>
      </div>

      <div v-if="vista === 'tabla'" class="rounded-lg border overflow-x-auto mb-8">
        <table class="min-w-full text-sm">
          <thead class="bg-muted text-foreground/80">
            <tr>
              <th class="text-left p-3"><button @click="ordenar('codigo')" class="underline">Código</button></th>
              <th class="text-left p-3"><button @click="ordenar('nombre')" class="underline">Nombre</button></th>
              <th class="text-left p-3">Área</th>
              <th class="text-left p-3">Modalidad</th>
              <th class="text-left p-3">Docente</th>
              <th class="text-left p-3">Responsable</th>
              <th class="text-left p-3">Avance</th>
              <th class="text-right p-3">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="curso in cursosList" :key="curso.id" class="border-t">
              <td class="p-3">{{ curso.codigo }}</td>
              <td class="p-3 font-medium">{{ curso.nombre }}</td>
              <td class="p-3">{{ curso.modalidadRel?.area?.nombre ?? '—' }}</td>
              <td class="p-3">{{ curso.modalidadRel?.nombre ?? curso.modalidad }}</td>
              <td class="p-3">{{ curso.docente?.nombre ?? '—' }}</td>
              <td class="p-3">{{ curso.responsable?.name ?? '—' }}</td>
              <td class="p-3">
                <div class="w-40">
                  <div class="text-xs mb-1">{{ curso.avance ?? 0 }}%</div>
                  <div class="h-2 bg-muted rounded"><div class="h-2 bg-green-600 rounded" :style="{ width: `${curso.avance ?? 0}%` }"></div></div>
                </div>
              </td>
              <td class="p-3 text-right whitespace-nowrap">
                <button class="p-1" title="Abrir" @click="verCurso(curso.id)">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 text-primary"><path d="M12 5C5 5 2 12 2 12s3 7 10 7 10-7 10-7-3-7-10-7Zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10Z"/></svg>
                </button>
                <button class="p-1" title="Drive" @click="abrirGoogleDrive(curso.drive_url)">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M7.71 3h8.58l4.3 7.45-4.29 7.55H7.7L3.41 10.45 7.7 3Zm9.67 8.45 2.5 4.4H9.55l-2.5-4.4h10.33ZM8.12 4.8 5.62 9.2h5.03L13.15 4.8H8.12Zm7.76 0h-2.98l-2.5 4.4h2.98l2.5-4.4Z"/></svg>
                </button>
                <button v-if="currentUserRole !== 'docente'" class="p-1" title="Editar" @click="editarCurso(curso.id)">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 text-yellow-600"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25Zm3.92 2.33H5v-1.92l8.06-8.06 1.92 1.92-8.06 8.06ZM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83Z"/></svg>
                </button>
                <button v-if="currentUserRole !== 'docente'" class="p-1" title="Eliminar" @click="eliminarCurso(curso.id)">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 text-red-600"><path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12ZM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4Z"/></svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <div class="flex items-center justify-between p-3">
          <div class="text-xs text-muted-foreground">Orden: {{ sort }} {{ dir }}</div>
          <div class="flex items-center gap-2">
            <template v-for="l in cursosLinks" :key="l.label">
              <button v-if="l.url" @click="cambiarPagina(l.url)" :class="['px-2 py-1 rounded border', l.active ? 'bg-primary text-primary-foreground' : '']" v-html="l.label"></button>
              <span v-else class="px-2 py-1 text-muted-foreground" v-html="l.label"></span>
            </template>
          </div>
        </div>
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-8">
        <div v-for="curso in cursosList" :key="curso.id" class="rounded-xl shadow bg-card border border-border overflow-hidden flex flex-col">
          <div class="h-16 bg-gradient-to-r from-green-600 to-blue-600 px-4 flex items-center text-white font-semibold">{{ curso.nombre }}</div>
          <div class="p-4 flex-1">
            <div class="text-sm text-muted-foreground mb-1">{{ curso.codigo }} · {{ curso.modalidadRel?.nombre ?? curso.modalidad }}</div>
            <div class="text-sm text-muted-foreground mb-1">Área: {{ curso.modalidadRel?.area?.nombre ?? '—' }}</div>
            <div class="text-sm mb-3">Docente: {{ curso.docente?.nombre ?? '—' }}</div>
            <div class="text-xs mb-1">Avance: {{ curso.avance ?? 0 }}%</div>
            <div class="h-2 bg-muted rounded"><div class="h-2 bg-green-600 rounded" :style="{ width: `${curso.avance ?? 0}%` }"></div></div>
          </div>
          <div class="p-3 border-t flex gap-3 justify-end">
            <button class="p-1" title="Abrir" @click="verCurso(curso.id)">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 text-primary"><path d="M12 5C5 5 2 12 2 12s3 7 10 7 10-7 10-7-3-7-10-7Zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10Z"/></svg>
            </button>
            <button class="p-1" title="Drive" @click="abrirGoogleDrive(curso.drive_url)">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M7.71 3h8.58l4.3 7.45-4.29 7.55H7.7L3.41 10.45 7.7 3Zm9.67 8.45 2.5 4.4H9.55l-2.5-4.4h10.33ZM8.12 4.8 5.62 9.2h5.03L13.15 4.8H8.12Zm7.76 0h-2.98l-2.5 4.4h2.98l2.5-4.4Z"/></svg>
            </button>
            <button v-if="currentUserRole !== 'docente'" class="p-1" title="Editar" @click="editarCurso(curso.id)">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 text-yellow-600"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25Zm3.92 2.33H5v-1.92l8.06-8.06 1.92 1.92-8.06 8.06ZM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83Z"/></svg>
            </button>
            <button v-if="currentUserRole !== 'docente'" class="p-1" title="Eliminar" @click="eliminarCurso(curso.id)">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 text-red-600"><path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12ZM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4Z"/></svg>
            </button>
          </div>
        </div>
      </div>

      <div v-if="cursosSinModalidad.length" class="mt-6">
        <h2 class="text-lg font-semibold mb-2">Cursos pendientes de Área/Modalidad</h2>
        <div class="rounded-lg border divide-y">
          <div v-for="curso in cursosSinModalidad" :key="curso.id" class="p-3 flex items-center justify-between">
            <div class="truncate">
              <div class="font-medium truncate">{{ curso.nombre }}</div>
              <div class="text-xs text-muted-foreground">Docente: {{ curso.docente?.nombre ?? '—' }} · Código: {{ curso.codigo }}</div>
            </div>
            <div class="flex items-center gap-2">
              <button class="underline" @click="verCurso(curso.id)">Abrir</button>
              <button v-if="currentUserRole !== 'docente'" class="underline text-yellow-600" @click="editarCurso(curso.id)">Completar datos</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
