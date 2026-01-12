<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const page = usePage();
const curso = page.props.curso as any;
const requerimientos = page.props.requerimientos as Record<string, { required: number }>;
const avance = page.props.avance as number;
const sedesCatalog = (page.props as any).sedesCatalog ?? [];
const currentUserRole = (page.props as any).currentUserRole ?? page.props.auth?.user?.role ?? null;
const currentUserId = (page.props as any).currentUserId ?? page.props.auth?.user?.id ?? null;

const tab = ref<'acta'|'recursos'|'registro'|'final'>('acta');
const subTab = ref<'guia'|'presentacion'|'trabajo'>('guia');
const semana = ref<string | number | ''>('');
const archivo = ref<File | null>(null);
const subiendo = ref(false);
const showToast = ref(false);
const toastMsg = ref('');
const toastType = ref<'success'|'error'>('success');
const semanaRequerida = computed(() => (tab.value === 'recursos' && (subTab.value === 'guia' || subTab.value === 'presentacion')));
const nombreArchivo = ref('');
const canReviewEvidence = computed(() => {
  if (currentUserRole === 'admin') return true;
  if (currentUserRole !== 'responsable') return false;
  if (!currentUserId) return false;
  return Number(curso.user_id) === Number(currentUserId);
});

function slugify(s: string) {
  return (s || '')
    .normalize('NFD').replace(/\p{Diacritic}/gu, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
}
const nombrePrevio = computed(() => {
  if (!archivo.value && !nombreArchivo.value) return '';
  const ext = archivo.value ? (archivo.value.name.split('.').pop() || '').toLowerCase() : '';
  const base = nombreArchivo.value ? slugify(nombreArchivo.value) : (archivo.value ? slugify(archivo.value.name.replace(/\.[^.]+$/,'')) : '');
  if (!base) return '';
  return base + (ext ? '.'+ext : '');
});

function toast(msg: string, type: 'success'|'error' = 'success') {
  toastMsg.value = msg;
  toastType.value = type;
  showToast.value = true;
  setTimeout(() => { showToast.value = false; }, 2500);
}

function seleccionarArchivo(e: Event) {
  const input = e.target as HTMLInputElement;
  archivo.value = input.files && input.files[0] ? input.files[0] : null;
}

function enviarEvidencia() {
  if (!archivo.value) { toast('Seleccione un archivo', 'error'); return; }
  if (semanaRequerida.value && !semana.value) { toast('Ingrese la semana', 'error'); return; }
  const data = new FormData();
  const tipo = subTab.value; // guia/presentacion/trabajo
  data.append('tipo', tipo);
  data.append('archivo', archivo.value);
  if (semana.value) data.append('semana', String(semana.value));
  if (nombreArchivo.value) data.append('nombre', nombreArchivo.value);
  if (tipo === 'trabajo') data.append('nivel', trabajoNivel.value || '');
  subiendo.value = true;
  router.post(`/cursos/${curso.id}/evidencias`, data, {
    forceFormData: true,
    onSuccess: () => { toast('Evidencia subida'); router.reload(); },
    onError: () => { toast('No se pudo subir la evidencia', 'error'); },
    onFinish: () => { subiendo.value = false; },
  });
}

const porTipo = (t: string) => (curso.evidencias ?? []).filter((e: any) => e.tipo === t);
const countFor = (t: string) => {
  if (t === 'acta') return (curso.actas ?? []).length || 0;
  if (t === 'recursos') return porTipo('guia').length + porTipo('presentacion').length + porTipo('trabajo').length;
  if (t === 'registro') return (curso.registroNotas ?? []).length || 0;
  if (t === 'final') return curso.informeFinal ? 1 : 0;
  return 0;
}

// --- Acta form state ---
const hoy = new Date();
const toDateInput = (d: Date) => d.toISOString().slice(0,10);
const actaForm = ref<any>({
  numero: String(((curso.actas ?? []).length || 0) + 1).padStart(2,'0'),
  fecha: toDateInput(hoy),
  hora_inicio: '',
  hora_fin: '',
  modalidad: curso.modalidad ?? '',
  responsable: curso.responsable?.name ?? '',
  asistentes: Array.from({length:5}).map(() => ({ nombre: '', campus: '', asistio: false })),
  acuerdos: Array.from({length:3}).map(() => ({ tema: '', acuerdo: '', responsable: '', fecha_entrega: '' })),
});

function addAsistente(){ actaForm.value.asistentes.push({ nombre: '', campus: '', asistio: false }); }
function removeAsistente(i:number){ actaForm.value.asistentes.splice(i,1); }
function addAcuerdo(){ actaForm.value.acuerdos.push({ tema: '', acuerdo: '', responsable: '', fecha_entrega: '' }); }
function removeAcuerdo(i:number){ actaForm.value.acuerdos.splice(i,1); }

function enviarActa(){
  // Simple cleanup: remove fully empty rows
  const payload = JSON.parse(JSON.stringify(actaForm.value));
  payload.asistentes = (payload.asistentes || []).filter((a:any)=> (a.nombre||'').trim() !== '' || (a.campus||'').trim() !== '' || !!a.asistio);
  payload.acuerdos = (payload.acuerdos || []).filter((a:any)=> (a.tema||a.acuerdo||a.responsable||a.fecha_entrega));
  if (!payload.fecha) { toast('Ingrese la fecha', 'error'); return; }
  router.post(`/cursos/${curso.id}/actas`, payload, {
    onSuccess: () => { toast('Acta registrada'); router.reload(); },
    onError: () => { toast('No se pudo registrar el acta','error'); },
  });
}

function eliminarActa(a:any){
  if (!confirm('¿Eliminar esta acta?')) return;
  router.delete(`/actas/${a.id}`, { onSuccess: () => { toast('Acta eliminada'); router.reload(); } });
}

function eliminar(ev: any) {
  if (!confirm('¿Eliminar este archivo?')) return;
  router.delete(`/evidencias/${ev.id}`, {
    onSuccess: () => { toast('Evidencia eliminada'); router.reload(); },
  });
}

function renombrar(ev: any) {
  const nombreActual = ev.archivo_path.split('/').pop();
  const nuevo = prompt('Nuevo nombre de archivo', nombreActual);
  if (!nuevo || nuevo === nombreActual) return;
  router.patch(`/evidencias/${ev.id}`, { nombre: nuevo }, {
    onSuccess: () => { toast('Evidencia renombrada'); router.reload(); },
  });
}

function revisarEvidencia(ev: any, estado: 'pendiente' | 'observado' | 'validado') {
  router.patch(`/evidencias/${ev.id}/review`, { estado }, {
    preserveScroll: true,
    onSuccess: () => { toast('Estado actualizado'); router.reload(); },
    onError: () => { toast('No se pudo actualizar el estado', 'error'); },
  });
}

function estadoLabel(estado?: string | null) {
  if (estado === 'validado') return 'Validado';
  if (estado === 'observado') return 'Observado';
  return 'Pendiente';
}

// Nivel para trabajos finales
const trabajoNivel = ref<'alto'|'medio'|'bajo'|''>('');

// Registro de notas
const registroForm = ref<any>({
  campus: '', sede_id: '', nrc: '', docente_nombre: '', total_estudiantes: 0,
  c1_aprobados: 0, c1_desaprobados: 0, c1_promedio: 0,
  ep_aprobados: 0, ep_desaprobados: 0, ep_promedio: 0,
  hipotesis_c1: '', hipotesis_ep: ''
});
function enviarRegistro(){
  const payload = { ...registroForm.value };
  if (payload.sede_id) {
    const found = sedesCatalog.find((s: any) => String(s.id) === String(payload.sede_id));
    if (found) {
      payload.campus = found.nombre;
    }
  }
  if (!payload.sede_id) {
    delete payload.sede_id;
  }
  router.post(`/cursos/${curso.id}/registro-notas`, payload, {
    onSuccess: () => { toast('Registro guardado'); router.reload(); },
    onError: () => { toast('No se pudo guardar','error'); }
  });
}

// Informe final (simplificado)
const informe = ref<any>({
  responsable: curso.responsable?.name ?? '',
  fecha_presentacion: '',
  resultados: {},
  mejoras: {},
  resultadosTexto: '',
  mejorasTexto: '',
});
// Prefill desde servidor si existe
if (curso.informeFinal) {
  informe.value.responsable = curso.informeFinal.responsable || informe.value.responsable;
  informe.value.fecha_presentacion = (curso.informeFinal.fecha_presentacion || '').slice(0,10);
  informe.value.resultados = curso.informeFinal.resultados || {};
  informe.value.mejoras = curso.informeFinal.mejoras || {};
  try { informe.value.resultadosTexto = JSON.stringify(informe.value.resultados, null, 2); } catch(e) {}
  try { informe.value.mejorasTexto = JSON.stringify(informe.value.mejoras, null, 2); } catch(e) {}
}
function guardarInforme(){
  try {
    informe.value.resultados = informe.value.resultadosTexto ? JSON.parse(informe.value.resultadosTexto) : {};
    informe.value.mejoras = informe.value.mejorasTexto ? JSON.parse(informe.value.mejorasTexto) : {};
  } catch(e) { /* ignore parse */ }
  router.post(`/cursos/${curso.id}/informe-final`, informe.value, {
    onSuccess: () => { toast('Informe final guardado'); router.reload(); },
    onError: () => { toast('No se pudo guardar el informe','error'); }
  });
}
</script>

<template>
  <AppLayout>
    <div class="p-6 min-h-screen bg-background text-foreground">
      <div class="mb-4">
        <h1 class="text-2xl font-bold">{{ curso.codigo }} - {{ curso.nombre }}</h1>
        <div class="text-sm text-muted-foreground">Docente: {{ curso.docente?.nombre ?? 'Sin asignar' }}</div>
      </div>

      <div class="mb-4">
        <div class="text-sm mb-1">Avance: {{ avance }}%</div>
        <div class="h-2 rounded bg-muted">
          <div class="h-2 rounded bg-green-600" :style="{ width: `${avance}%` }"></div>
        </div>
      </div>

      <div class="flex gap-2 mb-3">
        <button v-for="t in ['acta','recursos','registro','final']" :key="t" @click="tab = t as any"
          class="rounded px-3 py-1 border"
          :class="{ 'bg-primary text-primary-foreground': tab===t }">
          {{ t.charAt(0).toUpperCase() + t.slice(1) }}
          <span class="ml-1 text-xs text-muted-foreground" v-if="t!=='final'">({{ countFor(t) }})</span>
        </button>
      </div>

      <div class="rounded-xl border p-4 bg-card">
        <!-- Acta: render form instead of file upload -->
        <div v-if="tab==='acta'" class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="text-xs text-muted-foreground">N° Acta</label>
              <input v-model="actaForm.numero" class="w-full rounded border bg-background p-2" />
            </div>
            <div>
              <label class="text-xs text-muted-foreground">Fecha</label>
              <input v-model="actaForm.fecha" type="date" class="w-full rounded border bg-background p-2" />
            </div>
            <div>
              <label class="text-xs text-muted-foreground">Responsable de asignatura</label>
              <input v-model="actaForm.responsable" class="w-full rounded border bg-background p-2" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="text-xs text-muted-foreground">Hora inicio</label>
                <input v-model="actaForm.hora_inicio" type="time" class="w-full rounded border bg-background p-2" />
              </div>
              <div>
                <label class="text-xs text-muted-foreground">Hora fin</label>
                <input v-model="actaForm.hora_fin" type="time" class="w-full rounded border bg-background p-2" />
              </div>
            </div>
            <div>
              <label class="text-xs text-muted-foreground">Modalidad</label>
              <input v-model="actaForm.modalidad" class="w-full rounded border bg-background p-2" />
            </div>
          </div>

          <div>
            <div class="font-medium mb-2">Relación de docentes</div>
            <div class="space-y-2">
              <div v-for="(a,i) in actaForm.asistentes" :key="i" class="grid grid-cols-1 md:grid-cols-4 gap-2 items-center">
                <input v-model="a.nombre" placeholder="Apellidos y nombres" class="rounded border bg-background p-2 md:col-span-2" />
                <input v-model="a.campus" placeholder="Sede" class="rounded border bg-background p-2" />
                <label class="flex items-center gap-2 text-sm">
                  <input type="checkbox" v-model="a.asistio" /> Asistió
                </label>
                <button type="button" class="text-red-600 text-sm" @click="removeAsistente(i)" v-if="actaForm.asistentes.length>1">Quitar</button>
              </div>
              <button type="button" class="px-2 py-1 border rounded text-sm" @click="addAsistente">Agregar docente</button>
            </div>
          </div>

          <div>
            <div class="font-medium mb-2">Temas y acuerdos</div>
            <div class="space-y-2">
              <div v-for="(a,i) in actaForm.acuerdos" :key="i" class="grid grid-cols-1 md:grid-cols-4 gap-2 items-center">
                <input v-model="a.tema" placeholder="Temas tratados" class="rounded border bg-background p-2" />
                <input v-model="a.acuerdo" placeholder="Acuerdos" class="rounded border bg-background p-2 md:col-span-2" />
                <input v-model="a.responsable" placeholder="Responsables" class="rounded border bg-background p-2" />
                <input v-model="a.fecha_entrega" type="date" placeholder="Fecha de entrega" class="rounded border bg-background p-2" />
                <button type="button" class="text-red-600 text-sm" @click="removeAcuerdo(i)" v-if="actaForm.acuerdos.length>1">Quitar</button>
              </div>
              <button type="button" class="px-2 py-1 border rounded text-sm" @click="addAcuerdo">Agregar tema</button>
            </div>
          </div>

          <div class="flex justify-end">
            <button @click="enviarActa" class="px-3 py-1 rounded bg-primary text-primary-foreground">Guardar acta</button>
          </div>

          <div class="mt-6">
            <div class="font-medium mb-2">Actas registradas</div>
            <div class="space-y-2">
              <div v-for="a in (curso.actas ?? [])" :key="a.id" class="flex items-center justify-between rounded border p-2">
                <div>
                  <div class="text-sm font-medium">Acta N° {{ a.numero || '—' }} — {{ new Date(a.fecha).toLocaleDateString() }}</div>
                  <div class="text-xs text-muted-foreground">Resp.: {{ a.responsable || '—' }} — Modalidad: {{ a.modalidad || '—' }}</div>
                </div>
                <div class="flex items-center gap-2">
                  <button class="p-1" title="Eliminar" @click="eliminarActa(a)">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 text-red-600"><path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12ZM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4Z"/></svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Recursos académicos -->
        <div v-else-if="tab==='recursos'">
          <div class="mb-3 flex items-center gap-2">
            <button v-for="t in ['guia','presentacion','trabajo']" :key="t" @click="subTab = t as any" class="px-3 py-1 rounded border" :class="{ 'bg-muted': subTab===t }">
              {{ t==='guia' ? 'Guías de laboratorio' : (t==='presentacion' ? 'Presentaciones' : 'Trabajos finales') }}
            </button>
          </div>
          <div class="mb-3 flex items-center gap-2">
            <input v-model="semana" :required="semanaRequerida" type="number" min="1" class="w-24 rounded border bg-background p-1" :placeholder="semanaRequerida ? 'Semana*' : 'Semana'" />
            <input type="file" @change="seleccionarArchivo" class="rounded border bg-background p-1" />
            <input v-model="nombreArchivo" class="rounded border bg-background p-1" placeholder="Nombre (opcional)" />
            <select v-if="subTab==='trabajo'" v-model="trabajoNivel" class="rounded border bg-background p-1">
              <option value="">Nivel</option>
              <option value="alto">Alto</option>
              <option value="medio">Medio</option>
              <option value="bajo">Bajo</option>
            </select>
            <button @click="enviarEvidencia" :disabled="subiendo" class="px-3 py-1 rounded bg-primary text-primary-foreground">
              {{ subiendo ? 'Enviando…' : 'Enviar' }}
            </button>
          </div>
          <div v-if="nombrePrevio" class="text-xs text-muted-foreground mb-2">Se guardará como: <span class="font-medium">{{ nombrePrevio }}</span></div>
          <div class="space-y-2">
            <template v-for="ev in (subTab==='guia'?porTipo('guia'):subTab==='presentacion'?porTipo('presentacion'):porTipo('trabajo'))" :key="ev.id">
              <div class="flex items-center justify-between rounded border p-2">
                <div>
                  <div class="text-sm font-medium">{{ ev.archivo_path.split('/').pop() }}</div>
                  <div class="text-xs text-muted-foreground">
                    Subido: {{ new Date(ev.fecha_subida ?? ev.created_at).toLocaleString() }}
                    <span v-if="ev.nivel" class="ml-2">Nivel: {{ ev.nivel }}</span>
                    <span v-if="ev.semana" class="ml-2">Semana {{ ev.semana }}</span>
                  </div>
                  <div v-if="canReviewEvidence" class="text-xs mt-1">
                    Estado:
                    <span
                      :class="ev.estado === 'validado' ? 'text-emerald-600' : (ev.estado === 'observado' ? 'text-amber-600' : 'text-muted-foreground')"
                    >
                      {{ estadoLabel(ev.estado) }}
                    </span>
                  </div>
                </div>
                <div class="flex items-center gap-3">
                  <div v-if="canReviewEvidence" class="flex items-center gap-2">
                    <button
                      type="button"
                      class="px-2 py-1 text-xs rounded border border-emerald-600 text-emerald-700 hover:bg-emerald-50"
                      @click="revisarEvidencia(ev, 'validado')"
                    >
                      Validar
                    </button>
                    <button
                      type="button"
                      class="px-2 py-1 text-xs rounded border border-amber-500 text-amber-700 hover:bg-amber-50"
                      @click="revisarEvidencia(ev, 'observado')"
                    >
                      Observar
                    </button>
                    <button
                      type="button"
                      class="px-2 py-1 text-xs rounded border border-muted text-muted-foreground hover:bg-muted/40"
                      @click="revisarEvidencia(ev, 'pendiente')"
                    >
                      Pendiente
                    </button>
                  </div>
                  <a class="p-1" title="Ver" :href="`/storage/${ev.archivo_path}`" target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 text-primary"><path d="M12 5C5 5 2 12 2 12s3 7 10 7 10-7 10-7-3-7-10-7Zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10Z"/></svg>
                  </a>
                  <button class="p-1" title="Renombrar" @click="renombrar(ev)">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 text-yellow-600"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25Zm3.92 2.33H5v-1.92l8.06-8.06 1.92 1.92-8.06 8.06ZM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83Z"/></svg>
                  </button>
                  <button class="p-1" title="Eliminar" @click="eliminar(ev)">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 text-red-600"><path d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12ZM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4Z"/></svg>
                  </button>
                </div>
              </div>
            </template>
          </div>
        </div>

        <!-- Registro de notas -->
        <div v-else-if="tab==='registro'" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <select v-model="registroForm.sede_id" class="rounded border bg-background p-2">
              <option value="">Sede (catálogo)</option>
              <option v-for="s in sedesCatalog" :key="s.id" :value="s.id">
                {{ s.nombre }}
              </option>
            </select>
            <input v-model="registroForm.campus" placeholder="Sede (texto)" class="rounded border bg-background p-2" />
            <input v-model="registroForm.nrc" placeholder="NRC" class="rounded border bg-background p-2" />
            <input v-model="registroForm.docente_nombre" placeholder="Docente" class="rounded border bg-background p-2" />
            <input v-model.number="registroForm.total_estudiantes" type="number" min="0" placeholder="Total estudiantes" class="rounded border bg-background p-2" />
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="rounded border p-3">
              <div class="font-medium mb-2">Consolidado 1 (C1)</div>
              <div class="grid grid-cols-3 gap-2">
                <input v-model.number="registroForm.c1_aprobados" type="number" min="0" placeholder="Aprobados" class="rounded border bg-background p-2" />
                <input v-model.number="registroForm.c1_desaprobados" type="number" min="0" placeholder="Desaprobados" class="rounded border bg-background p-2" />
                <input v-model.number="registroForm.c1_promedio" type="number" step="0.01" min="0" placeholder="Promedio" class="rounded border bg-background p-2" />
              </div>
              <textarea v-model="registroForm.hipotesis_c1" class="w-full rounded border bg-background p-2 mt-2" placeholder="Hipótesis para C1"></textarea>
            </div>
            <div class="rounded border p-3">
              <div class="font-medium mb-2">Examen Parcial (EP)</div>
              <div class="grid grid-cols-3 gap-2">
                <input v-model.number="registroForm.ep_aprobados" type="number" min="0" placeholder="Aprobados" class="rounded border bg-background p-2" />
                <input v-model.number="registroForm.ep_desaprobados" type="number" min="0" placeholder="Desaprobados" class="rounded border bg-background p-2" />
                <input v-model.number="registroForm.ep_promedio" type="number" step="0.01" min="0" placeholder="Promedio" class="rounded border bg-background p-2" />
              </div>
              <textarea v-model="registroForm.hipotesis_ep" class="w-full rounded border bg-background p-2 mt-2" placeholder="Hipótesis para EP"></textarea>
            </div>
          </div>
          <div class="flex justify-end">
            <button @click="enviarRegistro" class="px-3 py-1 rounded bg-primary text-primary-foreground">Guardar registro</button>
          </div>

          <div v-if="Object.keys(($page.props as any).registroAggregate || {}).length" class="rounded border p-3">
            <div class="font-medium mb-2">Resultado final (consolidado)</div>
            <div v-for="(row,sede) in ($page.props as any).registroAggregate" :key="sede" class="grid grid-cols-5 gap-2 text-sm items-center border-b py-1">
              <div class="font-medium">{{ sede }}</div>
              <div>Total: {{ row.total_estudiantes }}</div>
              <div>C1: {{ row.c1_aprobados }} ({{ row.c1_porcentaje }}%)</div>
              <div>EP: {{ row.ep_aprobados }} ({{ row.ep_porcentaje }}%)</div>
              <div>Promedios C1/EP: {{ row.c1_promedio }} / {{ row.ep_promedio }}</div>
            </div>
          </div>
        </div>

        <!-- Informe Final -->
        <div v-else-if="tab==='final'" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <input v-model="informe.responsable" class="rounded border bg-background p-2" placeholder="Responsable de asignatura" />
            <input v-model="informe.fecha_presentacion" type="date" class="rounded border bg-background p-2" placeholder="Fecha de presentación" />
          </div>
          <div class="rounded border p-3">
            <div class="font-medium mb-2">Resultados por sede (simplificado)</div>
            <div class="text-xs text-muted-foreground mb-1">Opcional: pega JSON de resultados por sede.</div>
            <textarea v-model="informe.resultadosTexto" class="w-full h-24 rounded border bg-background p-2" placeholder='{"Huancayo": {"promedio_c1": 0, "ep_promedio": 0}}'></textarea>
          </div>
          <div class="rounded border p-3">
            <div class="font-medium mb-2">Oportunidades de mejora</div>
            <textarea v-model="informe.mejorasTexto" class="w-full h-24 rounded border bg-background p-2" placeholder='{"Huancayo": {"evaluaciones": "..."}}'></textarea>
          </div>
          <div class="flex justify-between items-center">
            <a v-if="curso.informeFinal" :href="`/cursos/${curso.id}/informe-final/preview`" target="_blank" class="px-3 py-1 rounded border">Imprimir / PDF</a>
            <button @click="guardarInforme" class="px-3 py-1 rounded bg-primary text-primary-foreground">Guardar informe final</button>
          </div>
        </div>
      </div>
      <div v-if="showToast" class="fixed bottom-4 right-4 px-4 py-2 rounded shadow text-sm" :class="toastType==='success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'">{{ toastMsg }}</div>
    </div>
  </AppLayout>
  
</template>

