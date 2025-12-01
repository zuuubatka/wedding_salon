document.addEventListener('DOMContentLoaded', () => {

    const guestModal = document.getElementById('choice-modal');
    const continueGuestBtn = document.getElementById('continue-guest');
    const openAuthBtn = document.getElementById('open-auth-modal');
    const authModal = document.getElementById('auth-modal');

    if (!guestModal || !continueGuestBtn || !openAuthBtn) return;

    // ===== Функция для получения куки =====
    function getCookie(name) {
        const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? match[2] : null;
    }

    // ===== Кнопки на странице =====
    const buttons = document.querySelectorAll('.btn-tryon, .btn-favorite');

    buttons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const clientId = getCookie('client_id');
            const guestId = getCookie('guest_id');

            // Показываем модалку только если пользователь не авторизован и нет guest_id
            if (!clientId && !guestId) {
                e.preventDefault(); 
                guestModal.style.display = 'flex';
            } 
            // Иначе можно выполнять действие кнопки напрямую (например, добавление в избранное)
        });
    });

    // ===== Продолжить как гость =====
    continueGuestBtn.addEventListener('click', () => {
        let guestId = getCookie('guest_id');
        if (!guestId) {
            const newGuestId = 'guest_' + Math.random().toString(36).substring(2, 12);
            document.cookie = `guest_id=${newGuestId}; path=/; max-age=${30*24*3600}`;
            console.log('Создан guest_id:', newGuestId);
        }
        guestModal.style.display = 'none';
    });

    // ===== Открыть авторизацию =====
    openAuthBtn.addEventListener('click', () => {
        guestModal.style.display = 'none';
        if (authModal) authModal.style.display = 'flex';
    });

    // ===== Закрытие модалки =====
    document.querySelectorAll('.modal').forEach(modal => {
        const closeBtn = modal.querySelector('.close-modal');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                modal.style.display = 'none';
            });
        }
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.style.display = 'none';
        });
    });

    // ===== Для быстрого теста: удалить все куки =====
   /* window.clearSiteCookies = function() {
        document.cookie.split(";").forEach(function(c) { 
            document.cookie = c.trim().split("=")[0] + '=; path=/; max-age=0'; 
        });
        console.log('Все куки удалены для теста');
    };*/

});
