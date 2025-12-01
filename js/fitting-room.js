document.addEventListener('click', function(e) {
    const btn = e.target.closest('.add-to-fitting-room');
    if (!btn) return;

    if (btn.classList.contains('loading')) return;
    btn.classList.add('loading');

    const productId = btn.dataset.productId;
    const selectedSize = document.getElementById('selected-size').value;

    const clientId = getCookie('client_id');
    const guestId  = getCookie('guest_id');

    if (!clientId && !guestId) {
        const guestModal = document.getElementById('choice-modal');
        if (guestModal) guestModal.style.display = 'flex';
        btn.classList.remove('loading');
        return;
    }

    if (!selectedSize) {
        alert('Выберите размер!');
        btn.classList.remove('loading');
        return;
    }

    const data = new FormData();
    data.append('action', 'add_to_fitting_room');
    data.append('product_id', productId);
    data.append('product_size_id', selectedSize);

    fetch(ajaxurl, { method: 'POST', body: data })
        .then(res => res.json())
        .then(res => {
            console.log('Ответ сервера:', res);

            // обновляем размеры
            if (typeof updateSizes === "function") {
                updateSizes(res.in_fitting || []);
            }

            // обновляем текст кнопки
            if (res.action === 'added') {
                btn.textContent = 'Удалить из примерочной';
            } else if (res.action === 'removed') {
                btn.textContent = 'В примерочную';
            }
        })
        .catch(err => console.error('Ошибка:', err))
        .finally(() => {
            btn.classList.remove('loading');
        });
});

// Cookie helper
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';')[0];
}

// =====================
// ОБНОВЛЕНИЕ РАЗМЕРОВ
// =====================
const sizeItems = document.querySelectorAll('.size-item'); // <--- вот правильный селектор
let currentlySelected = document.querySelector('.size-item.selected');
const fittingBtn = document.querySelector('.add-to-fitting-room');

function updateSizes(inFitting){
    inFitting = inFitting.map(String);

    sizeItems.forEach(item => {
        const sizeId = item.dataset.sizeId;

        // ❗ Если размер недоступен — не меняем его вообще
        if (item.classList.contains('unavailable')) {
            return;
        }

        // Сбрасываем стили доступных размеров
        item.classList.remove('in-fitting');
        item.style.backgroundColor = 'white';
        item.style.color = 'black';

        // Если размер находится в примерочной — подсвечиваем
        if (inFitting.includes(sizeId)) {
            item.classList.add('in-fitting');
            item.style.backgroundColor = 'pink';
            item.style.color = 'white';
        }
    });

    // Обновление кнопки
    if (currentlySelected) {
        fittingBtn.textContent = inFitting.includes(currentlySelected.dataset.sizeId)
            ? 'Удалить из примерочной'
            : 'В примерочную';
    }
}

