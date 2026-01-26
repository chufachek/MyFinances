<section class="page">
    <header class="page__header">
        <div>
            <h1>Создать аккаунт</h1>
            <p>Настройте профиль и получите аналитику расходов.</p>
        </div>
    </header>

    <div class="card card--form">
        <form class="form" method="post" action="/register">
            <label class="form__field">
                <span>Имя</span>
                <input type="text" name="name" placeholder="Алексей" required>
            </label>
            <label class="form__field">
                <span>Email</span>
                <input type="email" name="email" placeholder="you@example.com" required>
            </label>
            <label class="form__field">
                <span>Пароль</span>
                <input type="password" name="password" placeholder="Минимум 8 символов" required>
            </label>
            <div class="form__actions">
                <button class="button button--primary" type="submit">Зарегистрироваться</button>
                <a class="link" href="/login">Уже есть аккаунт</a>
            </div>
        </form>
    </div>
</section>
