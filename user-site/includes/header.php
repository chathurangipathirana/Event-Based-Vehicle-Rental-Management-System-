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
    <title>FleetElite | <?php echo $page_title ?? 'Event Vehicle Rental'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="assets/css/styles.css" />
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        /* Admin palette variables */
        :root {
            --surface: #f9f9fa;
            --surface-low: #f3f4f4;
            --surface-card: #ffffff;
            --surface-high: #e7e8e9;
            --primary: #02414a;
            --primary-soft: #b8ebf7;
            --primary-hover: #0d5260;
            --secondary: #5e5e5e;
            --tertiary: #6f4924;
            --outline: #c0c8ca;
            --text: #191c1d;
            --muted: #40484a;
            --success: #176a3a;
            --warning: #8a5200;
            --danger: #ba1a1a;
        }
        body { font-family: 'Inter', sans-serif; background-color: var(--surface); color: var(--text); }
        .form-input-focus:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(2,65,74,0.14);
            outline: none;
        }
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .alert-success { background: #dff5e8; color: #0f4d2b; border: 1px solid #a8dcc0; }
        .alert-error { background: #ffdad6; color: #93000a; border: 1px solid #ffb4ab; }
        /* Map common Tailwind red utilities to admin primary palette */
        .bg-primary { background-color: var(--primary) !important; }
        .text-primary { color: var(--primary) !important; }
        .border-primary { border-color: var(--primary) !important; }
        .hover\:bg-primary:hover { background-color: var(--primary-hover) !important; }
        .hover\:text-primary:hover { color: var(--primary-hover) !important; }
        .focus\:ring-primary:focus { --tw-ring-color: rgba(2,65,74,0.18) !important; }
        .focus\:border-primary:focus { border-color: var(--primary) !important; }
        .bg-red-600 { background-color: var(--primary) !important; }
        .bg-red-600 .text-red-100, .text-red-100 { color: rgba(255,255,255,0.78) !important; }
        .text-red-600 { color: var(--primary) !important; }
        .bg-red-50 { background-color: var(--primary-soft) !important; }
        .border-red-600 { border-color: var(--primary-soft) !important; }
        .border-red-500 { border-color: var(--danger) !important; }
        .text-red-500, .text-red-800 { color: var(--danger) !important; }
        .hover\:bg-red-500:hover { background-color: var(--danger) !important; }
        .focus\:ring-red-500:focus { box-shadow: 0 0 0 4px rgba(2,65,74,0.12) !important; }
        .focus\:border-red-500:focus { border-color: var(--primary) !important; }
        .bg-gray-50, .bg-background { background-color: var(--surface) !important; }
        .bg-surface-container-low, .bg-surface-container { background-color: var(--surface-low) !important; }
        .bg-surface-container-lowest, .bg-white { background-color: var(--surface-card) !important; }
        .border, .border-b, .border-t { border-color: var(--outline); }
        .shadow-lg, .shadow-md, .shadow-sm { box-shadow: 0 10px 24px rgba(25,28,29,0.06) !important; }
        .text-gray-900, .text-on-surface, .text-on-background { color: var(--text) !important; }
        .text-gray-600, .text-gray-500, .text-on-surface-variant { color: var(--muted) !important; }
        .text-green-600, .text-green-800 { color: var(--success) !important; }
        .text-yellow-600, .text-yellow-800 { color: var(--warning) !important; }
        .bg-green-100 { background-color: #dff5e8 !important; }
        .bg-yellow-100 { background-color: #fff3d6 !important; }
        .bg-blue-100 { background-color: var(--primary-soft) !important; }
        .text-blue-800 { color: var(--primary) !important; }
        input, select, textarea { border-color: var(--outline); }
        /* Border radius alignment across sites */
        .rounded-xl { border-radius: 0.75rem !important; }
        .rounded-lg { border-radius: 0.5rem !important; }
        .rounded-2xl { border-radius: 1rem !important; }
        .rounded-full { border-radius: 9999px !important; }
        /* Broad attribute selectors to catch any Tailwind red utilities remaining in markup */
        [class*="bg-red-"] { background-color: var(--primary) !important; }
        [class*="text-red-"] { color: var(--primary) !important; }
        [class*="border-red-"] { border-color: var(--primary-soft) !important; }
        [class*="hover:bg-red-"]:hover { background-color: var(--primary-hover) !important; }
        [class*="hover:text-red-"]:hover { color: var(--primary-hover) !important; }
        [class*="hover:border-red-"]:hover { border-color: var(--primary-hover) !important; }
        [class*="focus:ring-red-"]:focus { box-shadow: 0 0 0 4px rgba(2,65,74,0.12) !important; }
        [class*="focus:border-red-"]:focus { border-color: var(--primary) !important; }
        .bg-red-50, .bg-red-100 { background-color: var(--primary-soft) !important; }
        .bg-red-600 .text-red-100, .text-red-100 { color: rgba(255,255,255,0.78) !important; }
        .alert-warning { background: #fff3d6; color: #8a5200; border: 1px solid #ffeeba; }
    </style>
</head>
<body class="bg-background text-on-background antialiased overflow-x-hidden">
