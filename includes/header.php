<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TapInTapOut</title>
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon.png">
    <meta name="description" content="">

    <!-- Tailwind -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css?family=Karla:400,700&display=swap');
        .font-family-karla { font-family: karla; }
        
        .bg-sidebar { background: linear-gradient(180deg, #1e3a8a, #1e40af); }
        .cta-btn { color: #1e40af; }
        .upgrade-btn { background: #1e40af; }
        .upgrade-btn:hover { background: #1d4ed8; }

        .active-nav-link { background: #1d4ed8; }
        .nav-item:hover { background: #1e40af; }

        .account-link:hover { background: #1e40af; }

        /* Accent */
        .accent-icon { color: #22d3ee; }

        /* =========================
        SMOOTH DARK MODE TRANSITION
        ========================= */

        /* NO transitions by default */
        *,
        *::before,
        *::after {
            transition: none !important;
        }

        /* ENABLE transitions ONLY when toggling */
        html.theme-transition *,
        html.theme-transition *::before,
        html.theme-transition *::after {
            transition:
                background-color 0.35s ease,
                color 0.35s ease,
                border-color 0.35s ease,
                box-shadow 0.35s ease !important;
        }



        /* =========================
        DARK MODE (CLEAN VERSION)
        ========================= */

        .dark {
            background-color: #0f172a;
        }

        /* Main page background */
        .dark body {
            background-color: #0f172a;
        }

        /* Cards, tables, panels */
        .dark .bg-white {
            background-color: #1e293b !important;
        }

        /* Sidebar stays blue (LOGIN THEME) */
        .dark .bg-sidebar {
            background: linear-gradient(180deg, #1e3a8a, #1e40af);
        }

        /* Text colors */
        .dark .text-gray-800,
        .dark .text-gray-700,
        .dark .text-gray-600 {
            color: #e5e7eb !important;
        }

        .dark .text-gray-500 {
            color: #94a3b8 !important;
        }

        /* Borders */
        .dark .border,
        .dark .border-t,
        .dark .border-b {
            border-color: #334155 !important;
        }

        /* Tables */
        .dark thead {
            background-color: #1e293b !important;
        }

        .dark tbody tr:hover {
            background-color: #334155 !important;
        }

        /* Inputs */
        .dark input,
        .dark select,
        .dark textarea {
            background-color: #0f172a !important;
            color: #e5e7eb !important;
            border-color: #334155 !important;
        }

        /* Shadows (optional – cleaner look) */
        .dark .shadow,
        .dark .shadow-lg,
        .dark .shadow-xl {
            box-shadow: none !important;
        }

        .dark-text-main { color: #e5e7eb; }     /* gray-200 */
        .dark-text { color: #d1d5db; }          /* gray-300 */
        .dark-text-muted { color: #9ca3af; }

        /* 🌙 DARK MODE TEXT FIX */
        .dark .text-gray-600 { color: #d1d5db !important; } /* gray-300 */
        .dark .text-gray-700 { color: #e5e7eb !important; } /* gray-200 */
        .dark .text-gray-800 { color: #f3f4f6 !important; } /* gray-100 */
        .dark .text-gray-900 { color: #f9fafb !important; }

        /* Muted text */
        .dark .text-gray-500 { color: #9ca3af !important; }

        /* Headings */
        .dark h1,
        .dark h2,
        .dark h3,
        .dark h4 {
            color: #f9fafb;
        }


        /* 🌙 DARK MODE — FORCE READABLE TEXT */

        /* Default text inside cards */
        .dark .bg-white,
        .dark .bg-gray-50,
        .dark .bg-gray-100 {
            color: #e5e7eb; /* gray-200 */
        }

        /* Tables */
        .dark table,
        .dark th,
        .dark td {
            color: #e5e7eb;
        }

        /* Room name & activity text */
        .dark .room-name,
        .dark .activity-text,
        .dark .card-title {
            color: #f9fafb; /* near white */
        }

        /* If no class exists, catch common containers */
        .dark .rounded-xl,
        .dark .shadow,
        .dark .card,
        .dark .panel {
            color: #e5e7eb;
        }

        /* Muted timestamps */
        .dark .text-gray-500,
        .dark .text-xs {
            color: #9ca3af !important;
        }

        /* =========================
        DARK MODE BADGE FIXES
        ========================= */

        body.dark .badge-granted {
            background-color: rgba(16, 185, 129, 0.25); /* emerald */
            color: #6ee7b7;
        }

        body.dark .badge-denied {
            background-color: rgba(239, 68, 68, 0.25); /* red */
            color: #fca5a5;
        }

        body.dark .badge-override {
            background-color: rgba(139, 92, 246, 0.25); /* purple */
            color: #c4b5fd;
        }

        body.dark .badge-schedule {
            background-color: rgba(234, 179, 8, 0.25); /* yellow */
            color: #fde68a;
        }

        body.dark .badge-default {
            background-color: rgba(148, 163, 184, 0.25);
            color: #e5e7eb;
        }


    </style>

</head>