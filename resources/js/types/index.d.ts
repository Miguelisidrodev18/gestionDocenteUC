import type { PageProps } from '@inertiajs/core';
import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}
  
export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface SharedData extends PageProps {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    role: 'admin' | 'docente' | 'responsable';
}

export interface Docente {
    id: number;
    nombre: string;
    apellido: string;
    dni: string ;
    email: string ;
    telefono: string | null;
    especialidad: string;
    cv_sunedu: string | null;
    cv_docente: string | null;
    linkedin: string | null;
    estado: 'activo' | 'inactivo';
    cip: string | null;
    user_id: number | null;
    created_at: string;
    updated_at: string;
}

export interface CourseDocument {
    id: number;
    curso_id: number;
    docente_id: number;
    uploaded_by: number;
    path: string;
    mime: string;
    status: 'pendiente' | 'conforme_preliminar' | 'validado';
    created_at: string;
    updated_at: string;
    docente?: Docente | null;
}

export interface Cursos {
    id: number;
    nombre:string;
    codigo: string;
    descripcion: string;
    creditos: number;
    nivel: string;
    modalidad: string;
    drive_url?: string | null;
    periodo?: string;
    documents?: CourseDocument[];
    checklist_manual?: Record<string, string> | null;
    sede_id?: number | null;
    sede?: { id: number; nombre: string } | null;
    docente?: Docente | null;
    responsable?: User | null;
}
