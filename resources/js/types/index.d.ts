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
}

export type BreadcrumbItemType = BreadcrumbItem;

export interface Docente {
    id: number;
    nombre: string;
    apellido: string;
    dni: string ;
    email: string ;
    telefono: string | null;
    especialidad: string;
    cv_sunedu: string | null;
    cv_personal: string | null;
    linkedin: string | null;
    created_at: string;
    updated_at: string;
}

export interface Cursos {
    id: number;
    nombre:string;
    codigo: string;
    descripcion: string;
    creditos: number;
    nivel: string;

}