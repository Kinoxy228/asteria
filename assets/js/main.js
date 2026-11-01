// Астерия — клиентские скрипты

// Закрытие мобильного меню при переходе по ссылке
document.querySelectorAll('.main-nav a').forEach((link) => {
    link.addEventListener('click', () => document.body.classList.remove('nav-open'));
});

// Автоматическое скрытие уведомлений через 6 секунд
document.querySelectorAll('.flash-wrap .alert').forEach((alert) => {
    setTimeout(() => {
        alert.style.transition = 'opacity .4s';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 400);
    }, 6000);
});

// Подсказка знака зодиака по дате рождения в формах
const zodiac = [
    ['Козерог', 1, 19], ['Водолей', 2, 18], ['Рыбы', 3, 20], ['Овен', 4, 19], ['Телец', 5, 20], ['Близнецы', 6, 20],
    ['Рак', 7, 22], ['Лев', 8, 22], ['Дева', 9, 22], ['Весы', 10, 22], ['Скорпион', 11, 21], ['Стрелец', 12, 21], ['Козерог', 12, 31],
];
document.querySelectorAll('input[name="birth_date"]').forEach((input) => {
    const hint = document.createElement('span');
    hint.className = 'hint';
    input.after(hint);
    const update = () => {
        if (!input.value) { hint.textContent = ''; return; }
        const d = new Date(input.value);
        const m = d.getMonth() + 1, day = d.getDate();
        const sign = zodiac.find(([, mm, dd]) => m < mm || (m === mm && day <= dd));
        hint.textContent = sign ? 'Знак зодиака: ' + sign[0] : '';
    };
    input.addEventListener('change', update);
    update();
});
