<?php
require 'admin_common.php';

$table = $_GET['t'] ?? '';
if (!isset(ADMIN_TABLES[$table])) {
    header('Location: index.php');
    exit;
}
$columns = tableColumns($pdo, $table);
$pk = 'id';
$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);
$errors = [];

// ---------- Удаление записи ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    try {
        $pdo->prepare("DELETE FROM $table WHERE $pk = ?")->execute([(int)$_POST['delete']]);
        flash('success', 'Запись №' . (int)$_POST['delete'] . ' удалена.');
    } catch (PDOException $e) {
        flash('danger', 'Не удалось удалить запись: на неё ссылаются другие таблицы.');
    }
    header("Location: table.php?t=$table");
    exit;
}

// ---------- Добавление и изменение ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $data = [];
    foreach ($columns as $c) {
        if ($c['pk']) continue;
        $name = $c['name'];
        if ($name === 'created_at') continue;
        $value = trim($_POST[$name] ?? '');
        if ($table === 'users' && $name === 'password') {
            if ($value === '' && $action === 'edit') continue;           // пароль не меняется
            if ($value !== '') $value = password_hash($value, PASSWORD_DEFAULT);
        }
        if ($value === '' && $c['required']) $errors[] = "Поле «{$name}» обязательно для заполнения.";
        $data[$name] = $value === '' ? null : $value;
    }
    if (!$errors) {
        try {
            if ($action === 'edit') {
                $set = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));
                $pdo->prepare("UPDATE $table SET $set WHERE $pk = ?")->execute([...array_values($data), $id]);
                flash('success', "Запись №$id изменена.");
            } else {
                $cols = implode(', ', array_keys($data));
                $marks = implode(', ', array_fill(0, count($data), '?'));
                $pdo->prepare("INSERT INTO $table ($cols) VALUES ($marks)")->execute(array_values($data));
                flash('success', 'Запись №' . $pdo->lastInsertId() . ' добавлена.');
            }
            header("Location: table.php?t=$table");
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Ошибка базы данных: ' . $e->getMessage();
        }
    }
}

$row = [];
if ($action === 'edit') {
    $st = $pdo->prepare("SELECT * FROM $table WHERE $pk = ?");
    $st->execute([$id]);
    $row = $st->fetch() ?: [];
}

// ---------- Список записей с постраничным выводом ----------
$perPage = 15;
$page = max(1, (int)($_GET['page'] ?? 1));
$total = (int)$pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$offset = ($page - 1) * $perPage;
$rows = $pdo->query("SELECT * FROM $table ORDER BY $pk DESC LIMIT $perPage OFFSET $offset")->fetchAll();

// Подписи для внешних ключей
$fkLabels = [];
foreach ($columns as $c) {
    if (isset(FOREIGN_KEYS[$c['name']])) $fkLabels[$c['name']] = fkOptions($pdo, $c['name']);
}

function inputFor(array $c, $value, array $fkLabels, string $table, string $action): string
{
    $name = $c['name'];
    $v = e($value ?? '');
    if (isset($fkLabels[$name])) {
        $html = '<select name="' . $name . '"><option value="">— не выбрано —</option>';
        foreach ($fkLabels[$name] as $id => $label) {
            $html .= '<option value="' . $id . '"' . ((string)$value === (string)$id ? ' selected' : '') . '>' . $id . ' — ' . e($label) . '</option>';
        }
        return $html . '</select>';
    }
    if ($c['enum']) {
        $html = '<select name="' . $name . '">';
        foreach ($c['enum'] as $opt) $html .= '<option' . ($value === $opt ? ' selected' : '') . '>' . e($opt) . '</option>';
        return $html . '</select>';
    }
    if ($name === 'is_approved') {
        return '<select name="is_approved"><option value="0"' . ((string)$value === '0' || $value === null ? ' selected' : '') . '>0 — на модерации</option><option value="1"' . ((string)$value === '1' ? ' selected' : '') . '>1 — одобрен</option></select>';
    }
    if ($table === 'users' && $name === 'password') {
        return '<input type="text" name="password" placeholder="' . ($action === 'edit' ? 'Оставьте пустым, чтобы не менять' : 'Новый пароль') . '">';
    }
    $t = $c['type'];
    if (str_contains($t, 'text')) return '<textarea name="' . $name . '" rows="4">' . $v . '</textarea>';
    if ($t === 'date') return '<input type="date" name="' . $name . '" value="' . $v . '">';
    if ($t === 'time') return '<input type="time" step="1" name="' . $name . '" value="' . $v . '">';
    if (str_contains($t, 'int') || str_contains($t, 'decimal')) return '<input type="number" step="any" name="' . $name . '" value="' . $v . '">';
    return '<input type="text" name="' . $name . '" value="' . $v . '">';
}

adminHeader(ADMIN_TABLES[$table] . " ($table)", $table);
?>
<?php if ($action === 'add' || $action === 'edit'): ?>
    <form class="admin-form card" method="post">
        <h2><?= $action === 'edit' ? "Изменение записи №$id" : 'Новая запись' ?></h2>
        <?php foreach ($errors as $er): ?><div class="alert alert-danger"><?= e($er) ?></div><?php endforeach; ?>
        <div class="form-grid">
            <?php foreach ($columns as $c): if ($c['pk'] || $c['name'] === 'created_at') continue; ?>
                <label class="<?= str_contains($c['type'], 'text') ? 'wide' : '' ?>">
                    <span><?= e($c['name']) ?> <span class="muted small"><?= e($c['type']) ?></span><?= $c['required'] ? ' *' : '' ?></span>
                    <?= inputFor($c, $_POST[$c['name']] ?? ($row[$c['name']] ?? null), $fkLabels, $table, $action) ?>
                </label>
            <?php endforeach; ?>
        </div>
        <div class="form-actions">
            <button class="btn btn-gold" name="save" value="1"><?= icon('check-circle-outline') ?>Сохранить</button>
            <a class="btn btn-ghost" href="table.php?t=<?= $table ?>">Отмена</a>
        </div>
    </form>
<?php else: ?>
    <div class="admin-toolbar">
        <span class="muted">Всего записей: <?= $total ?></span>
        <a class="btn btn-gold btn-sm" href="table.php?t=<?= $table ?>&action=add"><?= icon('plus') ?>Добавить запись</a>
    </div>
    <div class="table-wrap card">
        <table class="table admin-table">
            <thead>
            <tr>
                <?php foreach ($columns as $c): ?><th><?= e($c['name']) ?></th><?php endforeach; ?>
                <th>Действия</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <?php foreach ($columns as $c): $val = $r[$c['name']]; ?>
                        <td class="<?= mb_strlen((string)$val) > 40 ? 'long' : '' ?>">
                            <?php if (isset($fkLabels[$c['name']]) && $val !== null): ?>
                                <span class="fk"><?= e($val) ?></span> <?= e($fkLabels[$c['name']][$val] ?? '') ?>
                            <?php elseif ($c['name'] === 'password'): ?>
                                <span class="muted small"><?= e(mb_strimwidth((string)$val, 0, 18, '…')) ?></span>
                            <?php else: ?>
                                <?= e(mb_strimwidth((string)$val, 0, 60, '…')) ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                    <td class="actions">
                        <a class="icon-btn" title="Изменить" href="table.php?t=<?= $table ?>&action=edit&id=<?= $r[$pk] ?>"><?= icon('pencil-outline') ?></a>
                        <form method="post" onsubmit="return confirm('Удалить запись №<?= $r[$pk] ?>?')">
                            <button class="icon-btn danger" title="Удалить" name="delete" value="<?= $r[$pk] ?>"><?= icon('delete-outline') ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pages > 1): ?>
        <div class="pagination">
            <?php for ($p = 1; $p <= $pages; $p++): ?>
                <a class="<?= $p === $page ? 'active' : '' ?>" href="table.php?t=<?= $table ?>&page=<?= $p ?>"><?= $p ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
<?php adminFooter(); ?>
