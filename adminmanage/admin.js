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
      // FormData автоматически подхватит файлы из input type="file"
      const data = new FormData(e.target);
      try {
        const response = await fetch(actionUrl, { method: "POST", body: data });
        const text = await response.text();
        let result;
        try {
          result = JSON.parse(text);
        } catch {
          console.error("Сервер прислал не JSON:", text);
          alert("Ошибка на сервере (ответ не JSON, см. консоль)");
          return;
        }
        if (result.success) {
          location.reload();
        } else {
          alert("Ошибка: " + (result.error || "Не удалось сохранить"));
        }
      } catch (err) {
        console.error("Ошибка сохранения:", err);
        alert("Ошибка связи с сервером");
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

  const selectedAnimIds = program.animator_ids || [];

  const animatorsHTML = allAnimators
    .map(
      (a) => `
    <div style="display: flex; align-items: center; margin-bottom: 5px;">
        <input type="checkbox" name="animators[]" value="${a.id}" id="anim_${a.id}" 
            ${selectedAnimIds.includes(parseInt(a.id)) ? "checked" : ""} 
            style="width: auto; margin-right: 10px;">
        <label for="anim_${a.id}" style="margin: 0; cursor: pointer;">
            ${a.name} <span style="color: gray; font-size: 11px;">(ID: ${a.id})</span>
        </label>
    </div>
  `,
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
                    
                    <div class="full-width" style="margin: 10px 0; border: 1px dashed #ccc; padding: 10px; border-radius: 5px;">
                        <strong>Кто может вести программу:</strong>
                        <div style="max-height: 120px; overflow-y: auto; margin-top: 5px;">
                            ${animatorsHTML}
                        </div>
                    </div>

                    <label>Длительность (мин): <input type="number" name="duration" value="${program.duration || ""}" required></label>
                    <label>Макс. детей: <input type="number" name="max_children" value="${program.max_children || ""}" required></label>
                    <label>Цена: <input type="number" name="price" value="${program.price || ""}" required></label>
                    
                    <label>Необходимое кол-во аниматоров: <input type="number" name="animator_count" value="${program.animator_count || "1"}" required></label>
                    
                    <label class="full-width">Изображение: 
                        <input type="file" name="image_file" accept="image/*">
                        <input type="hidden" name="old_image_path" value="${program.image_path || ""}">
                    </label>

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
                    
                    <label class="full-width">Изображение: 
                        <input type="file" name="image_file" accept="image/*">
                        <input type="hidden" name="old_path_image" value="${type.path_image || ""}">
                        ${type.path_image ? `<p style="font-size:12px; color:gray;">Текущий файл: ${type.path_image}</p>` : ""}
                    </label>

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

function updateBookingStatus(id, status) {
  if (!confirm("Изменить статус бронирования?")) return;

  fetch("adminmanage/admin_update_booking.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `id=${id}&status=${status}`,
  })
    .then((res) => {
      // Проверяем, что сервер вообще прислал JSON
      if (!res.ok) throw new Error("Сервер ответил ошибкой " + res.status);
      return res.json();
    })
    .then((data) => {
      if (data.success) {
        location.reload();
      } else {
        alert("Ошибка БД: " + (data.message || "Неизвестно"));
      }
    })
    .catch((err) => {
      console.error(err);
      alert("Критическая ошибка: " + err.message + ". Проверьте консоль (F12)");
    });
}

// --- КОМАНДА (Сотрудники) ---
function openTeamModal(member = {}) {
  // Подготавливаем список чекбоксов программ, которые может вести сотрудник
  // Используем глобальный массив allPrograms (убедитесь, что он выведен в PHP)
  const programsHTML = (typeof allPrograms !== "undefined" ? allPrograms : [])
    .map(
      (p) => `
    <div style="display: flex; align-items: center; margin-bottom: 5px;">
        <input type="checkbox" name="programs[]" value="${p.id}" id="team_prog_${p.id}" 
            ${(member.program_ids || []).map(Number).includes(parseInt(p.id)) ? "checked" : ""} 
            style="width: auto; margin-right: 10px;">
        <label for="team_prog_${p.id}" style="margin: 0; cursor: pointer; font-size: 13px;">
            ${p.name}
        </label>
    </div>
  `,
    )
    .join("");

  const modalHTML = `
        <div class="modal">
            <div class="modal-close" id="modal-close">×</div>
            <div class="modal-content">
                <h3>${member.id ? "Редактировать" : "Добавить"} сотрудника</h3>
                <form id="team-form">
                    <input type="hidden" name="id" value="${member.id || ""}">
                    
                    <label>Имя Фамилия: <input name="name" value="${member.name || ""}" required></label>
                    <label>Роль (позиция): <input name="role" value="${member.role || ""}" placeholder="Например: Ведущий" required></label>
                    <label>Email: <input type="email" name="email" value="${member.email || ""}" required></label>
                    
                    <div class="full-width" style="margin: 10px 0; border: 1px solid #eee; padding: 10px; border-radius: 5px; background: #f9f9f9;">
                        <strong>Специализация (программы):</strong>
                        <div style="max-height: 150px; overflow-y: auto; margin-top: 8px; padding-right: 5px;">
                            ${programsHTML || '<span style="color:gray">Программы не найдены</span>'}
                        </div>
                    </div>

                    <label class="full-width">Фото сотрудника: 
                        <input type="file" name="image_file" accept="image/*">
                        <input type="hidden" name="old_path_image" value="${member.path_image || ""}">
                        ${member.path_image ? `<p style="font-size:11px; color:green;">Файл загружен</p>` : ""}
                    </label>

                    <div class="modal-buttons">
                        <button type="submit">Сохранить</button>
                        <button type="button" id="modal-cancel">Отмена</button>
                    </div>
                </form>
            </div>
        </div>`;

  // Вызываем вашу универсальную функцию отрисовки
  renderModal(modalHTML, "team-form", "adminmanage/save_team_member.php");
}

async function editTeamMember(id) {
  try {
    const res = await fetch(`adminmanage/get_team_member.php?id=${id}`);
    const data = await res.json();
    openTeamModal(data);
  } catch (err) {
    console.error("Ошибка получения данных сотрудника:", err);
    alert("Не удалось загрузить данные");
  }
}

async function deleteTeamMember(id, name) {
  if (confirm(`Удалить сотрудника ${name}?`)) {
    try {
      const res = await fetch(`adminmanage/delete_team_member.php?id=${id}`);
      if (res.ok) location.reload();
    } catch (err) {
      console.error("Ошибка удаления:", err);
    }
  }
}

// --- ФОТОГАЛЕРЕЯ ---
// 1. Открытие модалки (Добавление/Редактирование)
function openGalleryModal(photo = {}) {
  const programOptions = (typeof allPrograms !== "undefined" ? allPrograms : [])
    .map(
      (p) =>
        `<option value="${p.id}" ${photo.program_id == p.id ? "selected" : ""}>${p.name}</option>`,
    )
    .join("");

  const modalHTML = `
        <div class="modal">
            <div class="modal-close" id="modal-close">×</div>
            <div class="modal-content">
                <h3>${photo.id ? "Редактировать" : "Добавить фото"}</h3>
                <form id="gallery-form">
                    <input type="hidden" name="id" value="${photo.id || ""}">
                    <label>Программа:
                        <select name="program_id" required style="width:100%; padding:8px; margin-top:5px;">
                            <option value="">-- Выберите программу --</option>
                            ${programOptions}
                        </select>
                    </label>
                    <label style="display:block; margin-top:15px;">
                        <input type="file" name="photos[]" accept="image/*" ${photo.id ? "" : "multiple required"}>
                    </label>
                    <div class="modal-buttons" style="margin-top:20px;">
                        <button type="submit" class="add-button">Сохранить</button>
                        <button type="button" id="modal-cancel">Отмена</button>
                    </div>
                </form>
            </div>
        </div>`;

  renderModal(modalHTML, "gallery-form", "adminmanage/save_gallery.php");
}
// 2. Редактирование (загрузка данных через PHP)
async function editGalleryPhoto(id) {
  try {
    const res = await fetch(`adminmanage/get_gallery_photo.php?id=${id}`);
    const data = await res.json();
    openGalleryModal(data);
  } catch (err) {
    alert("Ошибка загрузки данных");
  }
}

// 3. Удаление одного фото
async function deleteGalleryPhoto(id) {
  if (!confirm("Удалить это фото?")) return;
  const res = await fetch(`adminmanage/delete_gallery_photo.php?id=${id}`);
  if (res.ok) location.reload();
}

function filterGallery() {
  const filterValue = document
    .getElementById("gallery-filter")
    .value.toString();
  const rows = document.querySelectorAll(".gallery-row");
  const deleteBtn = document.getElementById("delete-all-btn");

  rows.forEach((row) => {
    // Получаем ID из атрибута data-program-id
    const rowProgramId = row.getAttribute("data-program-id")
      ? row.getAttribute("data-program-id").toString()
      : "";

    if (filterValue === "all" || rowProgramId === filterValue) {
      row.style.display = "";
    } else {
      row.style.display = "none";
    }
  });

  // Показываем кнопку удаления, только если выбрана конкретная программа
  deleteBtn.style.display = filterValue === "all" ? "none" : "block";
}

// 5. Удаление всех фото выбранной программы
async function deletePhotosByProgram() {
  const programId = document.getElementById("gallery-filter").value;
  const programName =
    document.getElementById("gallery-filter").options[
      document.getElementById("gallery-filter").selectedIndex
    ].text;

  if (!confirm(`Удалить ВСЕ фото программы "${programName}"?`)) return;

  const res = await fetch(
    `adminmanage/delete_all_program_photos.php?program_id=${programId}`,
  );
  const result = await res.json();
  if (result.success) location.reload();
}

// --- Смена аниматоров в заявке ---
function escapeHtmlBooking(str) {
  if (str == null || str === undefined) return "";
  const d = document.createElement("div");
  d.textContent = String(str);
  return d.innerHTML;
}

async function openBookingAnimatorsModal(bookingId) {
  const fd = new FormData();
  fd.append("booking_id", String(bookingId));
  let data;
  try {
    const res = await fetch("adminmanage/booking_animators_get.php", { method: "POST", body: fd });
    data = await res.json();
  } catch (e) {
    console.error(e);
    alert("Ошибка сети");
    return;
  }
  if (!data.success) {
    alert(data.message || "Ошибка загрузки");
    return;
  }
  const animatorCount = data.animator_count;
  const animators = data.animators || [];
  const currentIds = data.current_animator_ids || [];

  if (animators.length < animatorCount) {
    alert(
      `Недостаточно свободных аниматоров: доступно ${animators.length}, нужно ${animatorCount}.`,
    );
    return;
  }

  let selectsHtml = "";
  for (let i = 0; i < animatorCount; i++) {
    const cur = currentIds[i] != null ? String(currentIds[i]) : "";
    selectsHtml += `<label class="booking-anim-slot"><span>Аниматор ${i + 1}:</span><select class="booking-anim-sel" data-slot="${i}">`;
    selectsHtml += `<option value="">— выберите —</option>`;
    animators.forEach((a) => {
      const sel = String(a.id) === cur ? " selected" : "";
      selectsHtml += `<option value="${a.id}"${sel}>${escapeHtmlBooking(a.name)}</option>`;
    });
    selectsHtml += `</select></label>`;
  }

  const html = `
        <div class="modal booking-anim-modal">
            <div class="modal-close" id="modal-close">×</div>
            <div class="modal-content">
                <h3>Смена аниматоров</h3>
                <form id="booking-anim-form" class="booking-anim-form">
                    <p class="booking-anim-program">${escapeHtmlBooking(data.program_name)}</p>
                    <p class="booking-anim-date">Дата: ${escapeHtmlBooking(data.event_date)}</p>
                    <p class="booking-anim-desc">Доступны только аниматоры без других броней на эту дату, с рабочим днём по графику и с подходящей программой.</p>
                    ${selectsHtml}
                    <div class="modal-buttons">
                        <button type="submit">Сохранить</button>
                        <button type="button" id="modal-cancel">Отмена</button>
                    </div>
                </form>
            </div>
        </div>`;

  const container = document.getElementById("modal-container");
  container.innerHTML = html;
  container.classList.add("active");
  document.body.style.overflow = "hidden";

  const closeBtn = document.getElementById("modal-close");
  const cancelBtn = document.getElementById("modal-cancel");
  if (closeBtn) closeBtn.onclick = closeModal;
  if (cancelBtn) cancelBtn.onclick = closeModal;

  document.getElementById("booking-anim-form").onsubmit = async (e) => {
    e.preventDefault();
    const sels = container.querySelectorAll(".booking-anim-sel");
    const chosen = [];
    const seen = new Set();
    for (const s of sels) {
      const v = s.value;
      if (!v) {
        alert("Выберите всех аниматоров");
        return;
      }
      if (seen.has(v)) {
        alert("Нельзя выбрать одного и того же аниматора дважды");
        return;
      }
      seen.add(v);
      chosen.push(parseInt(v, 10));
    }
    let saveData;
    try {
      const saveRes = await fetch("adminmanage/booking_animators_save.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ booking_id: bookingId, animator_ids: chosen }),
      });
      saveData = await saveRes.json();
    } catch (e) {
      console.error(e);
      alert("Ошибка сети при сохранении");
      return;
    }
    if (!saveData.success) {
      alert(saveData.message || "Ошибка сохранения");
      return;
    }
    location.reload();
  };
}

// --- ВКЛАДКИ (С сохранением в localStorage) ---
document.querySelectorAll(".tab-button").forEach((btn) => {
  btn.addEventListener("click", () => {
    const tabId = btn.dataset.tab;
    localStorage.setItem("adminActiveTab", tabId); // Запоминаем вкладку

    document
      .querySelectorAll(".tab-content")
      .forEach((tab) => (tab.style.display = "none"));
    document.getElementById(tabId).style.display = "block";
    document
      .querySelectorAll(".tab-button")
      .forEach((b) => b.classList.remove("active"));
    btn.classList.add("active");
  });
});
