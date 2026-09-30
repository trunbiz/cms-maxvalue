<svg class="action-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
@switch($name)
@case('edit')<path d="m16 3 5 5-12 12-6 1 1-6Z"/><path d="m14 5 5 5"/>@break
@case('delete')<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/>@break
@case('copy')<rect x="8" y="8" width="13" height="13" rx="2"/><path d="M16 8V3H3v13h5"/>@break
@case('chapters')<path d="M4 4h16v16H4zM8 8h8M8 12h8M8 16h5"/>@break
@case('preview')<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>@break
@case('add')<path d="M12 4v16M4 12h16"/>@break
@endswitch
</svg>
