<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/style/auth-modal.css">

<div id="auth-modal" class="modal">
    <div class="modal-content">
        <form id="auth-form"  method="POST">
            <span class="close-modal">&times;</span>
            <h3>Авторизация</h3>

            <input type="hidden" name="access_key" value="c71c7fe0-1a25-4527-bf5a-3d52c702e880">
            <input type="hidden" name="code" id="generated_code">

            <input type="text" name="first_name" id="first_name" placeholder="Введите имя" required>
            <input type="text" name="phone" id="phone" placeholder="Введите телефон" required>
            <input type="email" name="email" id="email" placeholder="Введите email" required>

            <button type="submit" id="send-code-btn">Отправить код</button>

            <input type="text" id="code-input" placeholder="Введите код" style="display:none; margin-top:10px;">
            <button type="button" id="check-code-btn" style="display:none;">Проверить код</button>

            <p id="auth-message" style="color:red; margin-top:10px;"></p>
        </form>
    </div>
</div>

<script>
const form = document.getElementById('auth-form');
const sendBtn = document.getElementById('send-code-btn');
const checkBtn = document.getElementById('check-code-btn');
const emailInput = document.getElementById('email');
const phoneInput = document.getElementById('phone');
const nameInput = document.getElementById('first_name');
const codeInput = document.getElementById('code-input');
const generatedCodeInput = document.getElementById('generated_code');
const message = document.getElementById('auth-message');

sendBtn.addEventListener('click', async (e) => {
    e.preventDefault();

    message.textContent = 'Генерация кода...';

    const response = await fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'generate_auth_code',
            email: emailInput.value
        })
    });

    const data = await response.json();
    console.log("📥 Ответ сервера:", data);

    if (data.success) {
        message.style.color = 'green';
        message.textContent = data.message;
        codeInput.style.display = 'block';
        checkBtn.style.display = 'inline-block';
        sendBtn.style.display = 'none';
    } else {
        message.style.color = 'red';
        message.textContent = data.message;
    }
});

checkBtn.addEventListener('click', async () => {
    message.textContent = 'Проверка кода...';

    try {
        const response = await fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'verify_auth_code',
                email: emailInput.value,
                phone: phoneInput.value,
                first_name: nameInput.value,
                code: codeInput.value
            })
        });

        const data = await response.json();
        console.log("📥 Ответ сервера:", data);

        if (data.success) {
            message.style.color = 'green';
            message.textContent = 'Авторизация успешна!';

            // скрываем поля и кнопки
            codeInput.style.display = 'none';
            checkBtn.style.display = 'none';

            setTimeout(() => {
                window.location.reload(); // обновляем страницу
            }, 1000);
        } else {
            message.style.color = 'red';
            message.textContent = data.message || 'Неверный код';
        }
    } catch (error) {
        console.error('Ошибка запроса:', error);
        message.style.color = 'red';
        message.textContent = 'Ошибка при проверке кода';
    }
});


</script>
