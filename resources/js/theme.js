const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
const saved = localStorage.getItem('theme');
const isDark = saved ? saved === 'dark' : prefersDark;

document.documentElement.classList.toggle('dark', isDark);

export function toggleTheme() {
  const nowDark = !document.documentElement.classList.contains('dark');
  document.documentElement.classList.toggle('dark', nowDark);
  localStorage.setItem('theme', nowDark ? 'dark' : 'light');
}

