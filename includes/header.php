<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';
start_secure_session();
global $conn;
$cart_item_count = is_logged_in() ? get_cart_item_count($conn) : 0;
?>
<!DOCTYPE html>
<html lang="en" data-theme="unbound-light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unbound</title>
    <link rel="icon" type="image/svg+xml" href="<?= app_url('assets/favicon.svg') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root,
        [data-theme="unbound-dark"] {
            color-scheme: dark;
            --bg: #09090B;
            --fg: #FAFAFA;
            --muted: #27272A;
            --muted-fg: #A1A1AA;
            --accent: #DFE104;
            --accent-fg: #000000;
            --border: #3F3F46;
        }

        [data-theme="unbound-light"] {
            color-scheme: light;
            --bg: #FAFAFA;
            --fg: #09090B;
            --muted: #E4E4E7;
            --muted-fg: #52525B;
            --accent: #F5FF00;
            --accent-fg: #000000;
            --border: #A1A1AA;
        }
    </style>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        bg: 'var(--bg)',
                        fg: 'var(--fg)',
                        muted: 'var(--muted)',
                        'muted-fg': 'var(--muted-fg)',
                        accent: 'var(--accent)',
                        'accent-fg': 'var(--accent-fg)',
                        border: 'var(--border)'
                    },
                    fontFamily: {
                        display: ['Space Grotesk', 'sans-serif'],
                        body: ['Inter', 'sans-serif']
                    }
                }
            },
            daisyui: {
                themes: [
                    {
                        'unbound-dark': {
                            'base-100': '#09090B',
                            'base-200': '#27272A',
                            'base-300': '#3F3F46',
                            'base-content': '#FAFAFA',
                            'primary': '#DFE104',
                            'primary-content': '#000000',
                            'secondary': '#FAFAFA',
                            'secondary-content': '#09090B',
                            'accent': '#DFE104',
                            'accent-content': '#000000',
                            'neutral': '#27272A',
                            'neutral-content': '#FAFAFA',
                            '--rounded-box': '0',
                            '--rounded-btn': '0',
                            '--rounded-badge': '0'
                        }
                    },
                    {
                        'unbound-light': {
                            'base-100': '#FAFAFA',
                            'base-200': '#E4E4E7',
                            'base-300': '#A1A1AA',
                            'base-content': '#09090B',
                            'primary': '#F5FF00',
                            'primary-content': '#000000',
                            'secondary': '#09090B',
                            'secondary-content': '#FAFAFA',
                            'accent': '#F5FF00',
                            'accent-content': '#000000',
                            'neutral': '#E4E4E7',
                            'neutral-content': '#09090B',
                            '--rounded-box': '0',
                            '--rounded-btn': '0',
                            '--rounded-badge': '0'
                        }
                    }
                ]
            }
        };
    </script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="<?= app_url('assets/css/style.css?v=' . filemtime(__DIR__ . '/../assets/css/style.css')) ?>">
    <script>
        (function () {
            const savedTheme = localStorage.getItem('unbound-theme');
            if (savedTheme === 'unbound-light' || savedTheme === 'unbound-dark') {
                document.documentElement.setAttribute('data-theme', savedTheme);
            }
        })();
    </script>
</head>
<body class="buyer-body">
<a class="skip-link" href="#main-content">Skip to content</a>
<header class="site-header">
    <nav class="navbar">
        <a href="<?= app_url('index.php') ?>" class="logo">Unbound</a>
        <ul class="nav-links">
            <li><a class="nav-link" href="<?= app_url('index.php') ?>">Shop</a></li>
            <li><a class="nav-link" href="<?= app_url('about.php') ?>">About</a></li>
            <li>
                <a class="nav-link cart-nav-link" href="<?= app_url('cart.php') ?>">
                    <span>Cart</span>
                    <?php if ($cart_item_count > 0): ?>
                        <span class="cart-badge" aria-label="<?= (int) $cart_item_count ?> items in cart"><?= (int) $cart_item_count ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php if (is_logged_in()): ?>
                <li><a class="btn btn-sm btn-primary" href="<?= app_url('auth/logout.php') ?>">Logout</a></li>
            <?php else: ?>
                <li><a class="nav-link" href="<?= app_url('auth/login.php') ?>">Login</a></li>
                <li><a class="btn btn-sm btn-primary" href="<?= app_url('auth/register.php') ?>">Register</a></li>
            <?php endif; ?>
            <li>
                <button type="button" class="theme-toggle" data-theme-toggle aria-label="Toggle light and dark theme">
                    <span class="theme-icon theme-icon-sun" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false">
                            <circle cx="12" cy="12" r="4"></circle>
                            <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"></path>
                        </svg>
                    </span>
                    <span class="theme-icon theme-icon-moon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                        </svg>
                    </span>
                </button>
            </li>
        </ul>
    </nav>
</header>
<main id="main-content" class="site-main">
