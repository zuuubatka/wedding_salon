<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/style/modal.css">

<div id="globalModal" class="modal-overlay">
    <div class="modal-window">
        <button class="modal-close">&times;</button>
        <h2 class="modal-title"></h2>
        <p class="modal-message"></p>

        <div class="modal-buttons"></div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("globalModal");
    const closeBtn = modal.querySelector(".modal-close");

    closeBtn.addEventListener("click", () => closeModal());
    modal.addEventListener("click", e => {
        if (e.target === modal) closeModal();
    });
});

// универсальная функция показа модалки
function openModal({ title = "", message = "", buttons = [] }) {
    const modal = document.getElementById("globalModal");

    modal.querySelector(".modal-title").textContent = title;
    modal.querySelector(".modal-message").textContent = message;

    const btnContainer = modal.querySelector(".modal-buttons");
    btnContainer.innerHTML = "";

    // создаём кнопки
    buttons.forEach(btn => {
        const el = document.createElement("button");
        el.textContent = btn.text;
        el.className = btn.class || ""; // здесь теперь modal-confirm-btn / modal-cancel-btn
        el.addEventListener("click", () => {
            if (btn.onClick) btn.onClick();
        });
        btnContainer.appendChild(el);
    });

    modal.style.display = "flex";
}

function closeModal() {
    const modal = document.getElementById("globalModal");
    modal.style.display = "none";
}
</script>
