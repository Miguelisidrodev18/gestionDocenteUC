<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const page = usePage();
const curso = page.props.curso as any;
const requerimientos = page.props.requerimientos as Record<string, { required: number }>;
const avance = page.props.avance as number;

const tab = ref<'acta'|'guia'|'presentacion'|'trabajo'|'excel'>('acta');
const semana = ref<string | number | ''>('');
const archivo = ref<File | null>(null);
const subiendo = ref(false);
const showToast = ref(false);
const toastMsg = ref('');
const toastType = ref<'success'|'error'>('success');
const semanaRequerida = computed(() => tab.value === 'guia' || tab.value === 'presentacion');
const nombreArchivo = ref('');

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
  data.append('tipo', tab.value);
  data.append('archivo', archivo.value);
  if (semana.value) data.append('semana', String(semana.value));
  if (nombreArchivo.value) data.append('nombre', nombreArchivo.value);
  subiendo.value = true;
  router.post(`/cursos/${curso.id}/evidencias`, data, {
    forceFormData: true,
    onSuccess: () => { toast('Evidencia subida'); router.reload(); },
    onError: () => { toast('No se pudo subir la evidencia', 'error'); },
    onFinish: () => { subiendo.value = false; },
  });
}

const porTipo = (t: string) => (curso.evidencias ?? []).filter((e: any) => e.tipo === t);

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
        <button v-for="t in ['acta','guia','presentacion','trabajo','excel']" :key="t" @click="tab = t as any"
          class="rounded px-3 py-1 border"
          :class="{ 'bg-primary text-primary-foreground': tab===t }">
          {{ t.charAt(0).toUpperCase() + t.slice(1) }}
          <span class="ml-1 text-xs text-muted-foreground">({{ porTipo(t).length }} / {{ requerimientos[t]?.required ?? 0 }})</span>
        </button>
      </div>

      <div class="rounded-xl border p-4 bg-card">
        <div class="mb-3 flex items-center gap-2">
          <input v-model="semana" :required="semanaRequerida" type="number" min="1" class="w-24 rounded border bg-background p-1" :placeholder="semanaRequerida ? 'Semana*' : 'Semana'" />
          <input type="file" @change="seleccionarArchivo" class="rounded border bg-background p-1" />
          <input v-model="nombreArchivo" class="rounded border bg-background p-1" placeholder="Nombre (opcional)" />
          <button @click="enviarEvidencia" :disabled="subiendo" class="px-3 py-1 rounded bg-primary text-primary-foreground">
            {{ subiendo ? 'Enviando…' : 'Enviar' }}
          </button>
        </div>
        <div v-if="nombrePrevio" class="text-xs text-muted-foreground mb-2">Se guardará como: <span class="font-medium">{{ nombrePrevio }}</span> <span class="opacity-70">(podría agregarse -1 si existe)</span></div>
        <div class="space-y-2">
          <div v-for="ev in porTipo(tab)" :key="ev.id" class="flex items-center justify-between rounded border p-2">
            <div>
              <div class="text-sm font-medium">{{ ev.archivo_path.split('/').pop() }}</div>
              <div class="text-xs text-muted-foreground">Subido: {{ new Date(ev.fecha_subida ?? ev.created_at).toLocaleString() }}</div>
            </div>
            <div class="flex items-center gap-3">
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
        </div>
      </div>
      <div v-if="showToast" class="fixed bottom-4 right-4 px-4 py-2 rounded shadow text-sm" :class="toastType==='success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'">{{ toastMsg }}</div>
    </div>
  </AppLayout>
  
</template>

