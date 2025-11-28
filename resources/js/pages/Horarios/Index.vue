<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Meeting = {
  id: number;
  curso_id: number;
  title: string;
  description?: string;
  start_at: string;
  end_at: string;
  location?: string;
  curso?: { id: number; nombre: string; codigo: string };
};

const page = usePage();
const meetings = computed<Meeting[]>(() => page.props.meetings ?? []);
const cursos = computed<any[]>(() => page.props.cursos ?? []);
const flash: any = (page as any).props.flash ?? {};

const today = new Date();
const currentMonth = ref(new Date(today.getFullYear(), today.getMonth(), 1));

function nextMonth() {
  const d = new Date(currentMonth.value);
  d.setMonth(d.getMonth() + 1);
  currentMonth.value = d;
}
function prevMonth() {
  const d = new Date(currentMonth.value);
  d.setMonth(d.getMonth() - 1);
  currentMonth.value = d;
}

function monthLabel(d: Date) {
  return d.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
}

function daysInMonth(d: Date) {
  const year = d.getFullYear();
  const month = d.getMonth();
  const firstDay = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0);
  const start = new Date(firstDay);
  start.setDate(firstDay.getDate() - ((firstDay.getDay() + 6) % 7)); // start on Monday
  const end = new Date(lastDay);
  end.setDate(lastDay.getDate() + (7 - ((lastDay.getDay() + 6) % 7) - 1));
  const days: Date[] = [];
  const cur = new Date(start);
  while (cur <= end) {
    days.push(new Date(cur));
    cur.setDate(cur.getDate() + 1);
  }
  return days;
}

const showForm = ref(false);
const form = ref({
  curso_id: '',
  title: '',
  description: '',
  date: '',
  start_time: '09:00',
  end_time: '10:00',
  location: '',
});

function openFormFor(date: Date) {
  form.value.date = date.toISOString().slice(0, 10);
  showForm.value = true;
}

function submitMeeting() {
  const start_at = `${form.value.date} ${form.value.start_time}`;
  const end_at = `${form.value.date} ${form.value.end_time}`;
  router.post('/horarios/meetings', {
    curso_id: form.value.curso_id,
    title: form.value.title,
    description: form.value.description || undefined,
    start_at,
    end_at,
    location: form.value.location || undefined,
  }, {
    onSuccess: () => {
      showForm.value = false;
      form.value = { curso_id: '', title: '', description: '', date: '', start_time: '09:00', end_time: '10:00', location: '' };
      router.reload();
    }
  });
}

function eventsForDay(d: Date) {
  const day = d.toISOString().slice(0, 10);
  return meetings.value.filter(m => (m.start_at ?? '').slice(0,10) === day);
}
</script>

<template>
  <AppLayout>
    <div class="p-6 min-h-screen bg-background text-foreground">
      <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold">Calendario de reuniones</h1>
        <div class="flex items-center gap-2">
          <button class="px-2 py-1 rounded border" @click="prevMonth">&#x2039;</button>
          <span class="font-medium">{{ monthLabel(currentMonth) }}</span>
          <button class="px-2 py-1 rounded border" @click="nextMonth">&#x203A;</button>
        </div>
      </div>

      <div v-if="flash?.warning" class="mb-3 rounded border border-amber-400 bg-amber-50 px-3 py-2 text-xs text-amber-800">
        {{ flash.warning }}
      </div>

      <div class="grid grid-cols-7 gap-px rounded-lg overflow-hidden border bg-border">
        <div class="bg-muted p-2 text-xs font-medium text-muted-foreground">Lun</div>
        <div class="bg-muted p-2 text-xs font-medium text-muted-foreground">Mar</div>
        <div class="bg-muted p-2 text-xs font-medium text-muted-foreground">Mié</div>
        <div class="bg-muted p-2 text-xs font-medium text-muted-foreground">Jue</div>
        <div class="bg-muted p-2 text-xs font-medium text-muted-foreground">Vie</div>
        <div class="bg-muted p-2 text-xs font-medium text-muted-foreground">Sáb</div>
        <div class="bg-muted p-2 text-xs font-medium text-muted-foreground">Dom</div>

        <template v-for="(d, idx) in daysInMonth(currentMonth)" :key="idx">
          <div class="bg-card min-h-28 p-2 border-t border-border/50">
            <div class="flex items-center justify-between">
              <span class="text-xs text-muted-foreground">{{ d.getDate() }}</span>
              <button class="text-xs text-primary hover:underline" @click="openFormFor(d)">+ Reunión</button>
            </div>
            <div class="mt-2 space-y-1">
              <div v-for="m in eventsForDay(d)" :key="m.id" class="truncate text-xs rounded bg-primary/10 text-primary px-2 py-1">
                <span class="font-medium">{{ m.title }}</span>
                <span class="ml-1 text-[11px]">{{ (m.start_at as string).slice(11,16) }}-{{ (m.end_at as string).slice(11,16) }}</span>
              </div>
            </div>
          </div>
        </template>
      </div>

      <!-- Formulario de nueva reunión -->
      <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div class="w-full max-w-md rounded-xl border border-border bg-card p-4 shadow-xl">
          <h2 class="text-lg font-semibold mb-3">Nueva reunión</h2>
          <div class="grid gap-2">
            <label class="text-sm">Curso
              <select v-model="form.curso_id" class="mt-1 w-full rounded border bg-background p-2">
                <option value="">Seleccione un curso</option>
                <option v-for="c in cursos" :key="c.id" :value="c.id">{{ c.codigo }} - {{ c.nombre }}</option>
              </select>
            </label>
            <label class="text-sm">Título
              <input v-model="form.title" class="mt-1 w-full rounded border bg-background p-2" />
            </label>
            <label class="text-sm">Fecha
              <input v-model="form.date" type="date" class="mt-1 w-full rounded border bg-background p-2" />
            </label>
            <div class="grid grid-cols-2 gap-2">
              <label class="text-sm">Inicio
                <input v-model="form.start_time" type="time" class="mt-1 w-full rounded border bg-background p-2" />
              </label>
              <label class="text-sm">Fin
                <input v-model="form.end_time" type="time" class="mt-1 w-full rounded border bg-background p-2" />
              </label>
            </div>
            <label class="text-sm">Lugar / Link
              <input v-model="form.location" class="mt-1 w-full rounded border bg-background p-2" />
            </label>
            <label class="text-sm">Descripción
              <textarea v-model="form.description" rows="3" class="mt-1 w-full rounded border bg-background p-2"></textarea>
            </label>
          </div>
          <div class="mt-4 flex justify-end gap-2">
            <button class="rounded border px-3 py-1" @click="showForm = false">Cancelar</button>
            <button class="rounded bg-primary px-3 py-1 text-primary-foreground" @click="submitMeeting">Guardar</button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
