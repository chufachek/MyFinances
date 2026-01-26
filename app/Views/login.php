<section class="page">
    <header class="page__header">
        <div>
            <h1>Добро пожаловать</h1>
            <p>Войдите в личный кабинет управления финансами.</p>
        </div>
    </header>

    <div class="card card--form">
        <form class="form" method="post" action="/login">
            <label class="form__field">
                <span>Email</span>
                <input type="email" name="email" placeholder="you@example.com" required>
            </label>
            <label class="form__field">
                <span>Пароль</span>
                <input type="password" name="password" placeholder="••••••••" required>
            </label>
            <div class="form__actions">
                <button class="button button--primary" type="submit">Войти</button>
                <a class="link" href="/register">Нет аккаунта? Зарегистрироваться</a>
            </div>
        </form>
    </div>
</section>
