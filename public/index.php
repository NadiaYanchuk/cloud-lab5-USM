<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/config.php';

loadEnv(__DIR__ . '/../.env');

$errors = [];
$message = null;

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($title === '') {
            $errors[] = 'Title is required.';
        } else {
            $stmt = masterPdo()->prepare('INSERT INTO tasks (title, description) VALUES (:title, :description)');
            $stmt->execute(['title' => $title, 'description' => $description]);
            header('Location: /?msg=created');
            exit;
        }
    }

    if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($id <= 0 || $title === '') {
            $errors[] = 'Invalid update payload.';
        } else {
            $stmt = masterPdo()->prepare('UPDATE tasks SET title = :title, description = :description WHERE id = :id');
            $stmt->execute(['id' => $id, 'title' => $title, 'description' => $description]);
            header('Location: /?msg=updated');
            exit;
        }
    }

    if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = masterPdo()->prepare('DELETE FROM tasks WHERE id = :id');
            $stmt->execute(['id' => $id]);
            header('Location: /?msg=deleted');
            exit;
        }
        $errors[] = 'Invalid task id.';
    }

    $msg = $_GET['msg'] ?? '';
    if ($msg === 'created') {
        $message = 'Task created (write on master instance).';
    } elseif ($msg === 'updated') {
        $message = 'Task updated (write on master instance).';
    } elseif ($msg === 'deleted') {
        $message = 'Task deleted (write on master instance).';
    }

    $editTask = null;
    $editId = (int) ($_GET['edit'] ?? 0);
    if ($editId > 0) {
        $stmt = replicaPdo()->prepare('SELECT id, title, description FROM tasks WHERE id = :id');
        $stmt->execute(['id' => $editId]);
        $editTask = $stmt->fetch() ?: null;
    }

    $tasks = replicaPdo()->query('SELECT id, title, description, created_at, updated_at FROM tasks ORDER BY id DESC')->fetchAll();
} catch (Throwable $e) {
    $errors[] = $e->getMessage();
    $tasks = [];
    $editTask = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RDS CRUD Demo (PHP)</title>
    <style>
        body { font-family: Georgia, serif; margin: 2rem; background: #f5efe3; color: #2f2a24; }
        .card { background: #fffaf0; border: 1px solid #d7cab1; border-radius: 10px; padding: 1rem; margin-bottom: 1rem; }
        input, textarea, button { width: 100%; margin-top: 0.4rem; margin-bottom: 0.8rem; padding: 0.6rem; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { border: 1px solid #ddcfb4; padding: 0.6rem; text-align: left; }
        .row-actions { display: flex; gap: 0.5rem; }
        .row-actions form, .row-actions a { margin: 0; }
        .ok { color: #0f6b2e; }
        .err { color: #8f1f1f; }
        .muted { color: #6c6358; font-size: 0.95rem; }
    </style>
</head>
<body>
<h1>Amazon RDS CRUD Demo (PHP)</h1>
<p class="muted">Read operations use read replica (or master fallback). Create/Update/Delete use master instance.</p>

<?php if ($message !== null): ?>
    <p class="ok"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<?php foreach ($errors as $error): ?>
    <p class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
<?php endforeach; ?>

<div class="card">
    <h2><?= $editTask ? 'Update task' : 'Create task' ?></h2>
    <form method="post" action="/">
        <input type="hidden" name="action" value="<?= $editTask ? 'update' : 'create' ?>">
        <?php if ($editTask): ?>
            <input type="hidden" name="id" value="<?= (int) $editTask['id'] ?>">
        <?php endif; ?>

        <label>Title</label>
        <input type="text" name="title" required value="<?= htmlspecialchars($editTask['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <label>Description</label>
        <textarea name="description" rows="4"><?= htmlspecialchars($editTask['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

        <button type="submit"><?= $editTask ? 'Update' : 'Create' ?></button>
    </form>
</div>

<div class="card">
    <h2>Tasks (read from replica)</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Description</th>
                <th>Created</th>
                <th>Updated</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$tasks): ?>
                <tr><td colspan="6">No tasks found.</td></tr>
            <?php else: ?>
                <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td><?= (int) $task['id'] ?></td>
                        <td><?= htmlspecialchars($task['title'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $task['description'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $task['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $task['updated_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <div class="row-actions">
                                <a href="/?edit=<?= (int) $task['id'] ?>">Edit</a>
                                <form method="post" action="/" onsubmit="return confirm('Delete this record?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
                                    <button type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
