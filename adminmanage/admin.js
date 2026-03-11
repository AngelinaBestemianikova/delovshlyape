// Проверка загрузки файла (увидите в консоли)
console.log("Admin JS loaded");

// --- УНИВЕРСАЛЬНЫЕ ФУНКЦИИ ---

function closeModal() {
  const container = document.getElementById("modal-container");
  container.classList.remove("active");
  document.body.style.overflow = "";

  setTimeout(() => {
    container.innerHTML = "";
  }, 300);
}

// Закрытие по клику на серый фон
document.getElementById("modal-container").addEventListener("click", (e) => {
  if (e.target.id === "modal-container") {
    closeModal();
  }
});

// Вспомогательная функция для отрисовки любой модалки
function renderModal(html, formId, actionUrl) {
  const container = document.getElementById("modal-container");
  container.innerHTML = html;
  container.classList.add("active");
  document.body.style.overflow = "hidden";

  // Привязываем кнопки закрытия внутри новой модалки
  const closeBtn = document.getElementById("modal-close");
  const cancelBtn = document.getElementById("modal-cancel");

  if (closeBtn) closeBtn.onclick = closeModal;
  if (cancelBtn) cancelBtn.onclick = closeModal;

  // Отправка формы
  const form = document.getElementById(formId);
  if (form) {
    form.onsubmit = async (e) => {
      e.preventDefault();
      const data = new FormData(e.target);
      try {
        await fetch(actionUrl, { method: "POST", body: data });
        location.reload();
      } catch (err) {
        console.error("Ошибка сохранения:", err);
      }
    };
  }
}

// --- ПРОГРАММЫ ---

function openProgramModal(program = {}) {
  const typesOptions = programTypes
    .map(
      (t) =>
        `<option value="${t.id}" ${program.type_id == t.id ? "selected" : ""}>${t.name}</option>`,
    )
    .join("");

  const modalHTML = `
        <div class="modal">
            <div class="modal-close" id="modal-close">×</div>
            <div class="modal-content">
                <h3>${program.id ? "Редактировать" : "Добавить"} программу</h3>
                <form id="program-form">
                    <input type="hidden" name="id" value="${program.id || ""}">
                    <label>Название: <input name="name" value="${program.name || ""}" required></label>
                    <label>Тип: <select name="type_id">${typesOptions}</select></label>
                    <label class="full-width">Описание: <textarea name="description">${program.description || ""}</textarea></label>
                    <label class="full-width">Включенные услуги: <textarea name="included_services">${program.included_services || ""}</textarea></label>
                    <label>Длительность: <input type="number" name="duration" value="${program.duration || ""}" required></label>
                    <label>Макс. детей: <input type="number" name="max_children" value="${program.max_children || ""}" required></label>
                    <label>Цена: <input type="number" name="price" value="${program.price || ""}" required></label>
                    <label>Аниматоров: <input type="number" name="animator_count" value="${program.animator_count || ""}" required></label>
                    <label class="full-width">Путь к изображению: <input name="image_path" value="${program.image_path || ""}"></label>
                    <div class="modal-buttons">
                        <button type="submit">Сохранить</button>
                        <button type="button" id="modal-cancel">Отмена</button>
                    </div>
                </form>
            </div>
        </div>`;

  renderModal(modalHTML, "program-form", "adminmanage/save_program.php");
}

async function editProgram(id) {
  try {
    const res = await fetch(`adminmanage/get_program.php?id=${id}`);
    const data = await res.json();
    openProgramModal(data);
  } catch (err) {
    console.error("Ошибка получения данных программы:", err);
  }
}

async function deleteProgram(id) {
  if (confirm("Удалить программу?")) {
    await fetch(`adminmanage/delete_program.php?id=${id}`);
    location.reload();
  }
}

// --- ТИПЫ ПРОГРАММ ---

function openTypeModal(type = {}) {
  const modalHTML = `
        <div class="modal">
            <div class="modal-close" id="modal-close">×</div>
            <div class="modal-content">
                <h3>${type.id ? "Редактировать" : "Добавить"} тип</h3>
                <form id="type-form">
                    <input type="hidden" name="id" value="${type.id || ""}">
                    <label>Название: <input name="name" value="${type.name || ""}" required></label>
                    <label>Название для меню: <input name="name_for_menu" value="${type.name_for_menu || ""}"></label>
                    <label class="full-width">Описание: <textarea name="description">${type.description || ""}</textarea></label>
                    <label class="full-width">Путь к изображению: <input name="path_image" value="${type.path_image || ""}"></label>
                    <div class="modal-buttons">
                        <button type="submit">Сохранить</button>
                        <button type="button" id="modal-cancel">Отмена</button>
                    </div>
                </form>
            </div>
        </div>`;

  renderModal(modalHTML, "type-form", "adminmanage/save_type.php");
}

async function editType(id) {
  const res = await fetch(`adminmanage/get_type.php?id=${id}`);
  const data = await res.json();
  openTypeModal(data);
}

async function deleteType(id) {
  if (confirm("Удалить тип программы?")) {
    await fetch(`adminmanage/delete_type.php?id=${id}`);
    location.reload();
  }
}

// --- ВКЛАДКИ ---
document.querySelectorAll(".tab-button").forEach((btn) => {
  btn.addEventListener("click", () => {
    document
      .querySelectorAll(".tab-content")
      .forEach((tab) => (tab.style.display = "none"));
    document.getElementById(btn.dataset.tab).style.display = "block";
    document
      .querySelectorAll(".tab-button")
      .forEach((b) => b.classList.remove("active"));
    btn.classList.add("active");
  });
});
