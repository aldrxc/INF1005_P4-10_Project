<?php
// simple migration runner — visit this page after pulling to apply any new DB changes
// each file in sql/migrations/ runs once and is tracked in the migrations table

require_once __DIR__ . '/config/db.php';

$pdo = getDB();

// create the tracking table if it doesn't exist yet
$pdo->exec("
    CREATE TABLE IF NOT EXISTS migrations (
        id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        filename   VARCHAR(255) NOT NULL UNIQUE,
        run_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// grab all migration files in order
$files = glob(__DIR__ . '/sql/migrations/*.sql');
sort($files);

// find out which ones have already been run
$ran = $pdo->query("SELECT filename FROM migrations")->fetchAll(PDO::FETCH_COLUMN);
$ran = array_flip($ran);

$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run'])) {
    foreach ($files as $file) {
        $name = basename($file);
        if (isset($ran[$name])) {
            continue; // already done
        }

        try {
            $sql = file_get_contents($file);

            // run each statement separately (PDO doesn't support multiple statements in exec by default)
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
                if ($statement !== '') {
                    $pdo->exec($statement);
                }
            }

            $pdo->prepare("INSERT INTO migrations (filename) VALUES (?)")->execute([$name]);
            $results[$name] = ['ok', 'Ran successfully'];
        } catch (PDOException $e) {
            $results[$name] = ['err', $e->getMessage()];
        }
    }

    // reload so the status reflects the new state
    $ran = array_flip($pdo->query("SELECT filename FROM migrations")->fetchAll(PDO::FETCH_COLUMN));
}

$pending = array_filter($files, fn($f) => !isset($ran[basename($f)]));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MerchVault — Migrations</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body { background: #0d0d0d; color: #e0e0e0; }
        .card { background: #1a1a2e; border-color: #2a2a4a; }
        code { color: #e91e8c; }
    </style>
</head>
<body class="py-5">
<div class="container" style="max-width:720px">

    <h1 class="h3 fw-bold mb-1">MerchVault — DB Migrations</h1>
    <p class="text-muted mb-4">Run this after pulling to apply any new database changes.</p>

    <?php if (!empty($results)): ?>
        <?php foreach ($results as $name => [$status, $msg]): ?>
            <div class="alert alert-<?= $status === 'ok' ? 'success' : 'danger' ?> py-2">
                <code><?= htmlspecialchars($name) ?></code> —
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="card p-4 mb-4">
        <h2 class="h6 fw-semibold mb-3">All migrations</h2>
        <?php if (empty($files)): ?>
            <p class="text-muted small mb-0">No migration files found.</p>
        <?php else: ?>
            <ul class="list-unstyled mb-0">
                <?php foreach ($files as $file):
                    $name = basename($file);
                    $done = isset($ran[$name]);
                ?>
                    <li class="d-flex align-items-center gap-2 py-1 border-bottom border-secondary">
                        <?php if ($done): ?>
                            <span class="badge bg-success">done</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">pending</span>
                        <?php endif; ?>
                        <code class="small"><?= htmlspecialchars($name) ?></code>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <?php if (empty($pending)): ?>
        <div class="alert alert-success">
            Everything is up to date — no pending migrations.
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            <strong><?= count($pending) ?> pending migration<?= count($pending) !== 1 ? 's' : '' ?></strong>
            need to be run.
        </div>
        <form method="POST">
            <button name="run" value="1" class="btn btn-primary">
                Run <?= count($pending) ?> pending migration<?= count($pending) !== 1 ? 's' : '' ?>
            </button>
        </form>
    <?php endif; ?>

</div>
</body>
</html>
