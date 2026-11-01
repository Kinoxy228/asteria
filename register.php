<?php
require 'includes/db.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    header('Location: ' . url('account.php'));
    exit;
}

$errors = [];
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$birthDate = $_POST['birth_date'] ?? '';
$password = $_POST['password'] ?? '';
$password2 = $_POST['password2'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка на пустые поля
    if (empty($name)) $errors['name'] = 'Введите имя.';
    elseif (!preg_match('/^[А-ЯЁа-яёA-Za-z\s-]{2,50}$/u', $name)) $errors['name'] = 'Имя может содержать только буквы, пробел и дефис (от 2 до 50 символов).';

    if (empty($email)) $errors['email'] = 'Введите электронную почту.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Некорректный адрес электронной почты.';

    if ($birthDate !== '' && (strtotime($birthDate) === false || strtotime($birthDate) > time())) {
        $errors['birth_date'] = 'Дата рождения не может быть в будущем.';
    }

    // Проверка сложности пароля
    if (empty($password)) $errors['password'] = 'Введите пароль.';
    elseif (strlen($password) < 8) $errors['password'] = 'Пароль должен содержать не менее 8 символов.';
    elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) $errors['password'] = 'Пароль должен содержать латинские буквы и цифры.';
    elseif ($password !== $password2) $errors['password2'] = 'Пароли не совпадают.';

    // Проверка уникальности email
    if (!$errors) {
        $st = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $st->execute([$email]);
        if ($st->fetch()) $errors['email'] = 'Пользователь с такой почтой уже зарегистрирован.';
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare('INSERT INTO users (name, email, password, birth_date, role) VALUES (?, ?, ?, ?, ?)')
            ->execute([$name, $email, $hash, $birthDate ?: null, 'user']);
        flash('success', 'Регистрация прошла успешно. Войдите, используя почту и пароль.');
        header('Location: ' . url('login.php'));
        exit;
    }
}

$pageTitle = 'Регистрация';
$bodyClass = 'auth-page';
require 'includes/header.php';
?>
<section class="section auth-section">
    <div class="container auth-wrap">
        <form class="card auth-card" method="post">
            <div class="auth-icon"><?= icon('account-plus-outline') ?></div>
            <h1>Регистрация</h1>
            <p class="muted center">Создайте аккаунт, чтобы сохранять натальную карту и записываться к астрологам.</p>

            <label class="<?= isset($errors['name']) ? 'has-error' : '' ?>">Имя
                <input type="text" name="name" value="<?= e($name) ?>" placeholder="Иван">
                <?php if (isset($errors['name'])): ?><span class="error"><?= e($errors['name']) ?></span><?php endif; ?>
            </label>
            <label class="<?= isset($errors['email']) ? 'has-error' : '' ?>">Электронная почта
                <input type="email" name="email" value="<?= e($email) ?>" placeholder="name@mail.ru">
                <?php if (isset($errors['email'])): ?><span class="error"><?= e($errors['email']) ?></span><?php endif; ?>
            </label>
            <label class="<?= isset($errors['birth_date']) ? 'has-error' : '' ?>">Дата рождения <span class="muted small">(необязательно)</span>
                <input type="date" name="birth_date" value="<?= e($birthDate) ?>" max="<?= date('Y-m-d') ?>">
                <?php if (isset($errors['birth_date'])): ?><span class="error"><?= e($errors['birth_date']) ?></span><?php endif; ?>
            </label>
            <label class="<?= isset($errors['password']) ? 'has-error' : '' ?>">Пароль
                <input type="password" name="password" placeholder="Не менее 8 символов, буквы и цифры">
                <?php if (isset($errors['password'])): ?><span class="error"><?= e($errors['password']) ?></span><?php endif; ?>
            </label>
            <label class="<?= isset($errors['password2']) ? 'has-error' : '' ?>">Повторите пароль
                <input type="password" name="password2">
                <?php if (isset($errors['password2'])): ?><span class="error"><?= e($errors['password2']) ?></span><?php endif; ?>
            </label>
            <button class="btn btn-gold btn-block" type="submit">Зарегистрироваться</button>
            <p class="muted small center">Уже есть аккаунт? <a class="link" href="<?= url('login.php') ?>">Войти</a></p>
        </form>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
