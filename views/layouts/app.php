<?php
    $pageTitle = isset($title) ? $title . ' - ' . app_config('name') : app_config('name');
    $viewPath = dirname(__DIR__) . '/' . $name . '.php';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e(app_config('description')) ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        ink: '#17202a',
                        brand: '#0f766e',
                        coral: '#f97316',
                    },
                },
            },
        };
    </script>
</head>
<body class="min-h-screen bg-stone-50 text-ink antialiased">
    <?php require dirname(__DIR__) . '/partials/navbar.php'; ?>

    <main>
        <?php require $viewPath; ?>
    </main>

    <?php require dirname(__DIR__) . '/partials/confirm-dialog.php'; ?>
    <?php require dirname(__DIR__) . '/partials/footer.php'; ?>
</body>
</html>
