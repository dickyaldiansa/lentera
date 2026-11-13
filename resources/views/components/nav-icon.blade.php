@props(['name'])
<svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($name)
@case('dashboard')<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10M9 20v-6h6v6"/>@break
@case('application')<path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5M9 13h6M9 17h6"/>@break
@case('activity')<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18M8 14h3v3H8z"/>@break
@case('announcement')<path d="M5 14V9a7 7 0 0114 0v5l2 3H3zM9 20h6"/>@break
@case('institution')<path d="M3 21h18M5 21V9l7-5 7 5v12M9 13h2M13 13h2M9 17h2M13 17h2"/>@break
@case('payment')<circle cx="12" cy="12" r="9"/><path d="M15 8.5c-.7-.5-1.6-.8-2.6-.8-1.5 0-2.7.8-2.7 2s1.1 1.8 2.8 2.2 2.8.9 2.8 2.3-1.2 2.1-2.9 2.1c-1.1 0-2.2-.4-3-1M12.5 6v12"/>@break
@case('report')<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>@break
@case('education')<path d="M2 9l10-5 10 5-10 5zM6 11v5c3 3 9 3 12 0v-5M22 9v6"/>@break
@case('users')<circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2"/><path d="M3 20c0-4 2.5-6 6-6s6 2 6 6M15 15c3 0 5 1.7 5 5"/>@break
@case('rotation')<path d="M7 7h11l-3-3M17 17H6l3 3M20 7a8 8 0 01-1 8M4 17a8 8 0 011-8"/>@break
@case('logbook')<path d="M4 4h6a3 3 0 013 3v13a3 3 0 00-3-3H4zM20 4h-6a3 3 0 00-3 3M20 4v13h-6a3 3 0 00-3 3"/>@break
@case('assessment')<path d="M12 3l2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9z"/>@break
@case('supervisor')<circle cx="8" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M2 20c0-4 2.3-6 6-6s6 2 6 6M14 15c3.3 0 6 1.6 6 5"/>@break
@case('ethics')<path d="M12 3l8 3v5c0 5-3.4 8.7-8 10-4.6-1.3-8-5-8-10V6z"/><path d="M9 12l2 2 4-5"/>@break
@case('review')<circle cx="10" cy="10" r="6"/><path d="M14.5 14.5L21 21M8 10h4M10 8v4"/>@break
@case('certificate')<path d="M6 3h9l4 4v14H6zM14 3v5h5M9 14l2 2 4-4"/>@break
@case('monitoring')<path d="M4 19V5M4 19h16M7 15l4-4 3 2 5-6"/>@break
@case('database')<ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5M4 11v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/>@break
@default<circle cx="12" cy="12" r="8"/>@endswitch
</svg>
