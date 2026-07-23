<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Royal Lanka Rides Admin | <?php echo $page_title ?? 'Dashboard'; ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        :root {
            --surface: #f9f9fa;
            --surface-low: #f3f4f4;
            --surface-card: #ffffff;
            --surface-high: #e7e8e9;
            --primary: #00a8d8;
            --primary-soft: #d0eef7;
            --primary-hover: #0096c7;
            --secondary: #7d8f9e;
            --tertiary: #6f4924;
            --outline: #e0e0e0;
            --text: #191c1d;
            --muted: #5e5e5e;
            --success: #2ec66d;
            --warning: #f59e0b;
            --danger: #ef4444;
        }
        body { font-family: 'Inter', sans-serif; background-color: var(--surface); color: var(--text); }
        h1, h2, h3, h4, h5, h6 { font-family: 'Manrope', sans-serif; letter-spacing: 0; }
        main { background: var(--surface); }
        .bg-white { background-color: var(--surface-card) !important; }
        .bg-gray-50, .hover\:bg-gray-50:hover { background-color: var(--surface-low) !important; }
        .hover\:bg-gray-100:hover { background-color: rgba(232,236,241,0.08) !important; }
        .text-gray-900 { color: var(--text) !important; }
        .text-gray-600, .text-gray-500 { color: var(--muted) !important; }
        .text-gray-400 { color: var(--secondary) !important; }
        .text-red-600 { color: var(--primary) !important; }
        .bg-red-50 { background-color: rgba(0,168,216,0.12) !important; }
        .border-red-600 { border-color: var(--primary-soft) !important; }
        .border-gray-100, .border-gray-200 { border-color: var(--outline) !important; }
        .rounded-xl { border-radius: 0.75rem !important; }
        .rounded-lg { border-radius: 0.5rem !important; }
        .shadow-sm { box-shadow: 0 10px 24px rgba(25, 28, 29, 0.06) !important; }
        .text-green-600, .text-green-700 { color: var(--success) !important; }
        .text-orange-600, .text-yellow-700 { color: var(--warning) !important; }
        .bg-yellow-100 { background-color: #fff3d6 !important; }
        .bg-green-100 { background-color: #dff5e8 !important; }
        .bg-blue-100 { background-color: rgba(0, 168, 216, 0.15) !important; }
        .text-blue-700 { color: var(--primary) !important; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(2,65,74,0.45); z-index: 1000; overflow-y: auto; }
        .modal-content { background: var(--surface-card); margin: 50px auto; max-width: 600px; border-radius: 12px; border: 1px solid var(--outline); box-shadow: 0 24px 60px rgba(25,28,29,0.18); }
        .alert-success { background: #dff5e8; color: #0f4d2b; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #a8dcc0; }
        .alert-error { background: #ffdad6; color: #93000a; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ffb4ab; }
        /* Global overrides to map legacy red utilities to admin palette */
        .bg-red-600 { background-color: var(--primary) !important; }
        .text-red-600 { color: var(--primary) !important; }
        .bg-red-50 { background-color: rgba(0, 168, 216, 0.12) !important; }
        .border-red-600 { border-color: rgba(0, 168, 216, 0.3) !important; }
        .focus\:ring-red-500:focus { box-shadow: 0 0 0 4px rgba(0, 168, 216, 0.2) !important; }
        .focus\:border-red-500:focus { border-color: var(--primary) !important; }
        /* Border radius scale alignment */
        .rounded-xl { border-radius: 0.75rem !important; }
        .rounded-lg { border-radius: 0.5rem !important; }
        .rounded-2xl { border-radius: 1rem !important; }
        .rounded-full { border-radius: 9999px !important; }
        /* 3D card accent styles (used by KPI cards) */
        .card-3d { position: relative; overflow: visible; }
        .card-3d {
            position: relative;
            overflow: hidden;
            padding-right: 5.5rem;
            background: linear-gradient(180deg, #ffffff, #fbfbfd);
            border-radius: 0.75rem;
            border: 1px solid #d9dfe2;
            border-bottom: 4px solid var(--card-accent, #0b6b6d);
            box-shadow: 0 6px 16px rgba(2,65,74,0.06), 0 1px 4px rgba(2,65,74,0.03);
            transform: translateY(0);
            transition: transform .22s ease, box-shadow .22s ease;
            z-index: 1;
        }
        .card-3d:hover { transform: translateY(-6px); box-shadow: 0 18px 30px rgba(2,65,74,0.10); }

        .card-3d .card-icon {
            position: absolute;
            right: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            box-shadow: 0 8px 20px rgba(2,65,74,0.12);
            z-index: 2;
            flex-shrink: 0;
        }

        .kpi-value { font-size: 1.6rem; font-weight: 700; color: #072029; }
        .kpi-label { font-size: 0.85rem; color: #6b7280; margin-top: 0.25rem; }
        /* Broad attribute selectors to catch any Tailwind red utilities remaining in markup */
        [class*="bg-red-"] { background-color: var(--primary) !important; }
        [class*="text-red-"] { color: var(--primary) !important; }
        [class*="border-red-"] { border-color: rgba(0, 168, 216, 0.3) !important; }
        [class*="hover:bg-red-"]:hover { background-color: var(--primary-hover) !important; }
        [class*="hover:text-red-"]:hover { color: var(--primary-hover) !important; }
        [class*="hover:border-red-"]:hover { border-color: var(--primary-hover) !important; }
        [class*="focus:ring-red-"]:focus { box-shadow: 0 0 0 4px rgba(0, 168, 216, 0.2) !important; }
        [class*="focus:border-red-"]:focus { border-color: var(--primary) !important; }
        .bg-red-50, .bg-red-100 { background-color: rgba(0, 168, 216, 0.12) !important; }

        @media print {
            aside, .no-print, button, form, input, select, textarea, .flex-wrap, footer {
                display: none !important;
            }
            .ml-64, main {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            body, html {
                background: white !important;
                color: black !important;
            }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
