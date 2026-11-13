<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(yield_section('title', config('app.name', 'CnA Framework'))) ?></title>
    <?= \CnA\Frontend\Facades\Vite::csrf() ?>
    <?= \CnA\Frontend\Facades\Vite::assets('app') ?>
</head>
<body class="bg-slate-950 text-slate-100 font-sans antialiased min-h-screen">
    <div id="cna-app" class="flex flex-col min-h-screen">
        <main class="flex-1">
            <?= yield_section('content') ?>
        </main>
    </div>
</body>
</html>

