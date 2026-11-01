</main>
<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <a class="logo" href="<?= url('index.php') ?>">
                <img src="<?= url('assets/img/logo.svg') ?>" alt="" width="36" height="36">
                <span>Астерия</span>
            </a>
            <p class="muted">Астрологический портал: гороскопы на каждый день, персональная натальная карта и консультации профессиональных астрологов.</p>
        </div>
        <div>
            <h4>Разделы</h4>
            <a href="<?= url('horoscopes.php') ?>">Гороскопы</a>
            <a href="<?= url('personal.php') ?>">Персональный гороскоп</a>
            <a href="<?= url('astrologers.php') ?>">Астрологи</a>
            <a href="<?= url('articles.php') ?>">Статьи</a>
        </div>
        <div>
            <h4>Знаки зодиака</h4>
            <div class="footer-signs">
                <?php foreach (allSigns($pdo) as $s): ?>
                    <a href="<?= url('horoscopes.php?sign=' . $s['slug']) ?>" title="<?= e($s['name']) ?>"><?= zodiacIcon($s['slug']) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <div>
            <h4>Контакты</h4>
            <p class="muted"><?= icon('email-outline') ?> hello@asteria.ru</p>
            <p class="muted"><?= icon('phone-outline') ?> +7 (495) 123-45-67</p>
        </div>
    </div>
    <div class="container footer-bottom">© <?= date('Y') ?> Астерия. Гороскопы носят развлекательный характер.</div>
</footer>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>
