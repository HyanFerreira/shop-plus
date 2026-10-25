@props(['kind' => 'bags'])
@if ($kind === 'recovery')
    <svg class="auth-envelope" viewBox="0 0 160 136" fill="none" aria-hidden="true">
        <circle cx="80" cy="70" r="59" fill="currentColor" opacity=".05" />
        <path d="m31 57 49-37 49 37v59H31Z" fill="currentColor" opacity=".3" />
        <rect x="48" y="29" width="64" height="70" rx="6" fill="white" stroke="currentColor" stroke-opacity=".15" />
        <path d="M74 57v-5a7 7 0 0 1 14 0v5" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
        <rect x="70" y="56" width="22" height="20" rx="4" fill="currentColor" />
        <path d="M81 64v5" stroke="white" stroke-width="3" stroke-linecap="round" />
        <path d="m31 57 49 36 49-36v54a6 6 0 0 1-6 6H37a6 6 0 0 1-6-6Z" fill="currentColor" opacity=".35" />
        <path d="m33 114 35-29 12 9 12-9 35 29" fill="currentColor" opacity=".18" />
        <path d="m22 40 3-5 3 5-3 5Zm111-16 3-5 3 5-3 5ZM136 83l3-5 3 5-3 5Z" fill="currentColor" opacity=".4" />
    </svg>
@else
    <svg class="auth-bags" viewBox="0 0 280 190" fill="none" aria-hidden="true">
        <defs>
            <linearGradient id="auth-bag-blue" x1="112" y1="63" x2="177" y2="177" gradientUnits="userSpaceOnUse"><stop stop-color="#60A5FA"/><stop offset="1" stop-color="#2563EB"/></linearGradient>
            <linearGradient id="auth-bag-coral" x1="201" y1="92" x2="224" y2="180" gradientUnits="userSpaceOnUse"><stop stop-color="#FDA4AF"/><stop offset="1" stop-color="#E26480"/></linearGradient>
            <linearGradient id="auth-bag-sky" x1="52" y1="112" x2="83" y2="182" gradientUnits="userSpaceOnUse"><stop stop-color="#93C5FD"/><stop offset="1" stop-color="#3B82F6"/></linearGradient>
        </defs>
        <ellipse cx="143" cy="179" rx="113" ry="8" fill="#020617" opacity=".25"/>
        <path d="m100 64 66-8 17 117-91 5Z" fill="url(#auth-bag-blue)"/>
        <path d="m166 56 14 11 15 108-12-2Z" fill="#1D4ED8"/>
        <path d="m100 64 14 9 66-6-14-11Z" fill="#93C5FD"/>
        <path d="m115 78-2-24c-2-30 37-33 39-4l2 24" stroke="#BFDBFE" stroke-width="4" stroke-linecap="round"/>
        <path d="m187 100 44-5 11 80-60 3Z" fill="url(#auth-bag-coral)"/>
        <path d="m231 95 8 8 9 73-6-1Z" fill="#BE4C6B"/>
        <path d="m196 109-1-19c-1-22 25-24 27-3l1 19" stroke="#FECDD3" stroke-width="3" stroke-linecap="round"/>
        <path d="m42 122 42-5 8 61-56-1Z" fill="url(#auth-bag-sky)"/>
        <path d="m84 117 7 7 7 53-6 1Z" fill="#2563EB"/>
        <path d="m51 130 1-18c1-22 24-21 24-2v17" stroke="#BFDBFE" stroke-width="3" stroke-linecap="round"/>
        <path d="m226 45 3-7 3 7 7 3-7 3-3 7-3-7-7-3Z" fill="#93C5FD" opacity=".7"/>
        <circle cx="58" cy="78" r="3" fill="#93C5FD" opacity=".6"/>
    </svg>
@endif
