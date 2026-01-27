<section class="auth">
    <div class="auth__card">
        <h1>Регистрация</h1>
        <p class="text-muted">Создайте аккаунт и начните учет.</p>
        <form id="register-form" class="stack">
            <label class="field">
                <span>Имя</span>
                <input type="text" name="full_name" placeholder="Анна Петрова">
            </label>
            <label class="field">
                <span>Email</span>
                <input type="email" name="email" required>
            </label>
            <label class="field">
                <span>Пароль</span>
                <input type="password" name="password" required minlength="6">
            </label>
            <label class="field">
                <span>Повторите пароль</span>
                <input type="password" name="password_confirm" required minlength="6">
            </label>
            <button class="btn btn-primary" type="submit">Создать аккаунт</button>
        </form>
        <p class="text-muted">Уже есть аккаунт? <a class="link" href="<?= htmlspecialchars(($basePath ?? '') . '/login', ENT_QUOTES) ?>">Войти</a></p>
    </div>
</section>
