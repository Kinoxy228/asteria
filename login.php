<?php
require 'includes/db.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    header('Location: ' . url('account.php'));
    exit;
}

$errors = [];
$email = trim($_POST['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    if (empty($email)) $errors['email'] = 'Введите электронную почту.';
    if (empty($password)) $errors['password'] = 'Введите пароль.';

    if (!$errors) {
        // Поиск пользователя по email (подготовленный запрос)
        $st = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $st->execute([$email]);
        $user = $st->fetch();
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            header('Location: ' . url($user['role'] === 'admin' ? 'admin/index.php' : 'account.php'));
            exit;
        }
        $errors['common'] = 'Неверный email или пароль.';
    }
}

$pageTitle = 'Вход';
$bodyClass = 'auth-page';
require 'includes/header.php';
?>
<section class="section auth-section">
    <div class="container auth-wrap">
        <form class="card auth-card" method="post">
            <div class="auth-icon"><?= icon('login') ?></div>
            <h1>Вход в личный кабинет</h1>
            <p class="muted center">Ваша натальная карта и записи на консультации — в одном месте.</p>
            <?php if (isset($errors['common'])): ?><div class="alert alert-danger"><?= icon('alert-circle-outline') ?><?= e($errors['common']) ?></div><?php endif; ?>
            <label class="<?= isset($errors['email']) ? 'has-error' : '' ?>">Электронная почта
                <input type="email" name="email" value="<?= e($email) ?>" placeholder="name@mail.ru">
                <?php if (isset($errors['email'])): ?><span class="error"><?= e($errors['email']) ?></span><?php endif; ?>
            </label>
            <label class="<?= isset($errors['password']) ? 'has-error' : '' ?>">Пароль
                <input type="password" name="password" placeholder="Ваш пароль">
                <?php if (isset($errors['password'])): ?><span class="error"><?= e($errors['password']) ?></span><?php endif; ?>
            </label>
            <button class="btn btn-gold btn-block" type="submit">Войти</button>
            <p class="muted small center">Нет аккаунта? <a class="link" href="<?= url('register.php') ?>">Зарегистрироваться</a></p>
        </form>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
