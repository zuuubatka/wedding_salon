document.addEventListener('DOMContentLoaded', () => {

    function getCookie(name) {
        const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? match[2] : null;
    }

    const favoriteButtons = document.querySelectorAll('.btn-favorite');

    favoriteButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();

            const productId = btn.dataset.productId;
            const clientId = getCookie('client_id');
            const guestId = getCookie('guest_id');

            if (!clientId && !guestId) {
                const guestModal = document.getElementById('choice-modal');
                if (guestModal) guestModal.style.display = 'flex';
                return;
            }

            const img = btn.querySelector('img');

            // 🟣 Надёжная проверка — работает с любыми путями
            const isFavorite = img.src.endsWith('pink-heart.svg') || img.src.includes('pink-heart.svg');

            const action = isFavorite ? 'remove_from_favorite' : 'add_to_favorite';

            fetch(myAjax.ajax_url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: action,
                    product_id: productId,
                    client_id: clientId || '',
                    guest_id: guestId || ''
                })
            })
            .then(res => res.json())
            .then(data => {
                console.log('AJAX favorite response:', data);

                if (data.success) {
                    // 🟣 Меняем иконку сердечка НАДЁЖНО

                    if (isFavorite) {
                        img.src = img.src.replace('pink-heart.svg', 'heart.svg');
                    } else {
                        img.src = img.src.replace('heart.svg', 'pink-heart.svg');
                    }
                } else {
                    alert('Ошибка при обновлении избранного');
                }
            })
            .catch(err => console.error('AJAX fetch error:', err));

        });
    });

});
