<svg class="action-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
@switch($name)
@case('edit')<path d="m16 3 5 5-12 12-6 1 1-6Z"/><path d="m14 5 5 5"/>@break
@case('delete')<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/>@break
@case('copy')<rect x="8" y="8" width="13" height="13" rx="2"/><path d="M16 8V3H3v13h5"/>@break
@case('chapters')<path d="M4 4h16v16H4zM8 8h8M8 12h8M8 16h5"/>@break
@case('preview')<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>@break
@case('add')<path d="M12 4v16M4 12h16"/>@break
@case('back')<path d="m12 5-7 7 7 7M5 12h15"/>@break
@case('dashboard')<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>@break
@case('books')<path d="M3 4h5v16H3zM8 4h5v16H8zM15 5l4-1 3 15-4 1zM3 8h10"/>@break
@case('folder')<path d="M3 7V5a2 2 0 0 1 2-2h5l2 3h7a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/>@break
@case('tag')<path d="M3 3h8l10 10-8 8L3 11V3Z"/><circle cx="7.5" cy="7.5" r="1"/>@break
@case('settings')<path d="M4 6h16M4 12h16M4 18h16"/><circle cx="8" cy="6" r="2" fill="var(--admin-sidebar-bg, #fff)"/><circle cx="16" cy="12" r="2" fill="var(--admin-sidebar-bg, #fff)"/><circle cx="10" cy="18" r="2" fill="var(--admin-sidebar-bg, #fff)"/>@break
@case('users')<circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 5a3 3 0 0 1 0 6M18 15a5 5 0 0 1 3 5v1"/>@break
@case('shield')<path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6l8-3Z"/><path d="m8 12 3 3 5-6"/>@break
@case('external')<path d="M14 3h7v7M21 3l-9 9M10 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-5"/>@break
@endswitch
</svg>
