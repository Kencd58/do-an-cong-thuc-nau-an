document.querySelectorAll('[data-recipe-image]').forEach(image => {
    const fallback = () => {
        if (image.dataset.fallbackUsed) return;
        image.dataset.fallbackUsed = 'true';
        image.src = image.dataset.fallback;
    };
    image.addEventListener('error', fallback, { once: true });
    if (image.complete && image.naturalWidth === 0) fallback();
});

const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
const sidebarBackdrop = document.querySelector('[data-sidebar-close]');
const sidebar = document.querySelector('#app-sidebar');

if (sidebarToggle && sidebar && sidebarBackdrop) {
    const setSidebar = open => {
        document.body.classList.toggle('sidebar-open', open);
        sidebarToggle.setAttribute('aria-expanded', String(open));
        sidebarBackdrop.hidden = !open;
        if (open) sidebar.querySelector('a, button')?.focus();
    };

    sidebarToggle.addEventListener('click', () => setSidebar(!document.body.classList.contains('sidebar-open')));
    sidebarBackdrop.addEventListener('click', () => setSidebar(false));
    sidebar.addEventListener('click', event => {
        if (event.target.closest('a') && window.matchMedia('(max-width: 1020px)').matches) setSidebar(false);
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && document.body.classList.contains('sidebar-open')) {
            setSidebar(false);
            sidebarToggle.focus();
        }
    });
    window.addEventListener('resize', () => {
        if (window.innerWidth > 1020 && document.body.classList.contains('sidebar-open')) setSidebar(false);
    });
}

document.querySelectorAll('[data-confirm-delete]').forEach(form => {
    form.addEventListener('submit', event => {
        if (!window.confirm(form.dataset.confirmDelete || 'Xóa công thức này? Thao tác này không thể hoàn tác.')) event.preventDefault();
    });
});

document.querySelectorAll('[data-back-link]').forEach(link => {
    link.addEventListener('click', event => {
        if (!document.referrer || window.history.length <= 1) return;
        try {
            const previous = new URL(document.referrer);
            if (previous.origin !== window.location.origin || previous.href === window.location.href) return;
            event.preventDefault();
            window.history.back();
        } catch (_) {
            // The regular link remains the safe fallback.
        }
    });
});

document.querySelectorAll('[data-form-cancel]').forEach(button => {
    button.addEventListener('click', () => window.location.assign(button.dataset.href));
});

document.querySelectorAll('[data-auto-submit]').forEach(control => {
    control.addEventListener('change', () => control.form?.requestSubmit());
});

document.querySelectorAll('.filter-panel').forEach(filterForm => {
    filterForm.addEventListener('submit', () => {
        filterForm.setAttribute('aria-busy', 'true');
        filterForm.classList.add('is-submitting');
        const submit = filterForm.querySelector('button[type="submit"]');
        if (submit) submit.textContent = 'Đang cập nhật…';
    });
});

document.querySelectorAll('[data-comment-toggle]').forEach(button => {
    button.addEventListener('click', () => {
        const editForm = document.getElementById(button.dataset.commentToggle);
        editForm.hidden = !editForm.hidden;
        button.setAttribute('aria-expanded', String(!editForm.hidden));
        if (!editForm.hidden) editForm.querySelector('textarea').focus();
    });
});

const form = document.querySelector('[data-recipe-form]');
if (form) {
    const ingredientContainer = form.querySelector('[data-rows="ingredients"]');
    const ingredientPicker = form.querySelector('[data-ingredient-picker]');
    const ingredientPickerPanel = form.querySelector('[data-ingredient-picker-panel]');
    const ingredientSearch = form.querySelector('[data-ingredient-search]');
    const ingredientSuggestionsClose = form.querySelector('[data-ingredient-suggestions-close]');
    const ingredientOptions = [...form.querySelectorAll('[data-ingredient-option]')];
    const ingredientFeedback = form.querySelector('[data-ingredient-feedback]');
    let suppressIngredientPickerFocus = false;

    const setIngredientPicker = (open, restoreFocus = false) => {
        if (!ingredientPickerPanel || !ingredientSearch) return;
        ingredientPickerPanel.hidden = !open;
        ingredientSearch.setAttribute('aria-expanded', String(open));
        ingredientSuggestionsClose.hidden = !open;
        ingredientPicker.classList.toggle('is-open', open);
        if (restoreFocus) {
            suppressIngredientPickerFocus = true;
            ingredientSearch.focus();
            suppressIngredientPickerFocus = false;
        }
    };

    ingredientSearch?.addEventListener('focus', () => {
        if (!suppressIngredientPickerFocus) setIngredientPicker(true);
    });
    ingredientSearch?.addEventListener('click', () => setIngredientPicker(true));
    ingredientSuggestionsClose?.addEventListener('click', () => setIngredientPicker(false, true));

    document.addEventListener('click', event => {
        if (!ingredientPickerPanel?.hidden && !ingredientPicker.contains(event.target)) {
            setIngredientPicker(false);
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !ingredientPickerPanel?.hidden) {
            setIngredientPicker(false, true);
        }
    });

    const updateSectionErrors = () => {
        form.querySelectorAll('[data-form-section]').forEach(section => {
            const errors = section.querySelectorAll('.field-error:not([hidden]), [aria-invalid="true"]').length;
            const badge = section.querySelector('[data-section-error]');
            section.classList.toggle('has-error', errors > 0);
            if (!badge) return;
            badge.hidden = errors === 0;
            badge.textContent = errors ? 'Cần kiểm tra' : '';
        });
    };

    const updateIngredientState = () => {
        if (!ingredientContainer) return;
        const selected = [...ingredientContainer.querySelectorAll('[data-ingredient]')]
            .map(select => select.value).filter(Boolean);
        const count = form.querySelector('[data-ingredient-count]');
        const empty = form.querySelector('[data-selected-empty]');
        if (count) count.textContent = selected.length;
        if (empty) empty.hidden = selected.length > 0;
        ingredientOptions.forEach(option => {
            const added = selected.includes(option.dataset.id);
            option.classList.toggle('is-added', added);
            const button = option.querySelector('[data-ingredient-add]');
            if (button) {
                button.disabled = added;
                button.textContent = added ? 'Đã thêm' : 'Thêm';
            }
        });
    };

    const syncIngredientSummary = row => {
        const ingredient = row.querySelector('[data-ingredient]');
        const quantity = row.querySelector('input[name$="[quantity]"]');
        const unit = row.querySelector('[data-unit-select]');
        const note = row.querySelector('input[name$="[note]"]');
        const name = ingredient?.selectedOptions[0]?.textContent.trim() || '';
        const amount = [quantity?.value, unit?.value].filter(Boolean).join(' ');
        const summaryNote = row.querySelector('[data-summary-note]');
        row.querySelector('[data-summary-name]').textContent = name;
        row.querySelector('[data-summary-amount]').textContent = amount;
        summaryNote.textContent = note?.value.trim() || '';
        summaryNote.hidden = !summaryNote.textContent;
    };

    const setIngredientMode = (row, confirmed) => {
        const editor = row.querySelector('[data-ingredient-editor]');
        const summary = row.querySelector('[data-ingredient-summary]');
        row.dataset.confirmed = String(confirmed);
        row.classList.toggle('is-confirmed', confirmed);
        row.classList.toggle('is-editing', !confirmed);
        editor.hidden = confirmed;
        summary.hidden = !confirmed;
        row.querySelector('[data-ingredient-edit]')?.setAttribute('aria-expanded', String(!confirmed));
    };

    const confirmIngredientRow = row => {
        let firstInvalid = null;
        const fields = [...row.querySelectorAll('[data-ingredient-editor] input[required], [data-ingredient-editor] select[required]')];
        fields.forEach(field => {
            field.setCustomValidity('');
            if (field.checkValidity()) {
                if (field.dataset.clientErrorKey) clearClientError(field);
                return;
            }
            setFieldError(field, validationMessage(field));
            firstInvalid ||= field;
        });

        const ingredient = row.querySelector('[data-ingredient]');
        const duplicate = [...ingredientContainer.querySelectorAll('[data-ingredient]')]
            .some(other => other !== ingredient && other.value && other.value === ingredient.value);
        if (duplicate) {
            setFieldError(ingredient, 'Nguyên liệu này đã được thêm trước đó.');
            firstInvalid ||= ingredient;
        }

        if (firstInvalid) {
            updateSectionErrors();
            firstInvalid.focus();
            return;
        }

        syncIngredientSummary(row);
        setIngredientMode(row, true);
        ingredientFeedback.textContent = `Đã xác nhận ${ingredient.selectedOptions[0].textContent.trim()}.`;
        row.querySelector('[data-ingredient-summary]').focus();
        updateSectionErrors();
    };

    const reindex = container => {
        const kind = container.dataset.rows;
        container.querySelectorAll('[data-row]').forEach((row, index) => {
            row.querySelector('[data-number]').textContent = index + 1;
            row.querySelectorAll('[name], [id], [for], [aria-describedby]').forEach(element => {
                for (const attr of ['name', 'id', 'for', 'aria-describedby']) {
                    if (!element.hasAttribute(attr)) continue;
                    const value = element.getAttribute(attr)
                        .replace(new RegExp(`${kind}\\[[^\\]]+\\]`, 'g'), `${kind}[${index}]`)
                        .replace(new RegExp(`${kind}-(?:\\d+|__INDEX__)-`, 'g'), `${kind}-${index}-`)
                        .replace(new RegExp(`${kind}\\.(?:\\d+|__INDEX__)(?=\\.|-error)`, 'g'), `${kind}.${index}`);
                    element.setAttribute(attr, value);
                }
            });
            const remove = row.querySelector('[data-remove]');
            if (remove) remove.disabled = kind === 'steps' && container.children.length <= 1;
        });
        const add = form.querySelector(`[data-add="${kind}"]`);
        if (add) add.disabled = container.children.length >= Number(container.dataset.max);
        if (kind === 'ingredients') updateIngredientState();
    };

    const addIngredient = button => {
        if (!ingredientContainer || ingredientContainer.children.length >= Number(ingredientContainer.dataset.max)) return;
        const existing = [...ingredientContainer.querySelectorAll('[data-ingredient]')]
            .some(select => select.value === button.dataset.id);
        if (existing) {
            ingredientFeedback.textContent = 'Nguyên liệu này đã có trong danh sách.';
            return;
        }
        ingredientContainer.append(document.querySelector('#ingredients-template').content.cloneNode(true));
        reindex(ingredientContainer);
        const row = ingredientContainer.lastElementChild;
        const ingredient = row.querySelector('[data-ingredient]');
        const unit = row.querySelector('[data-unit-select]');
        ingredient.value = button.dataset.id;
        unit.value = button.dataset.unit || '';
        ingredientFeedback.textContent = 'Đã thêm nguyên liệu. Hãy nhập định lượng rồi xác nhận.';
        reindex(ingredientContainer);
        ingredientSearch.value = '';
        ingredientOptions.forEach(option => { option.hidden = false; });
        const empty = form.querySelector('[data-ingredient-empty]');
        if (empty) empty.hidden = true;
        setIngredientPicker(false);
        row.querySelector('input[type="number"]')?.focus();
    };

    ingredientSearch?.addEventListener('input', () => {
        setIngredientPicker(true);
        const keyword = ingredientSearch.value.trim().toLocaleLowerCase('vi');
        let visible = 0;
        ingredientOptions.forEach(option => {
            const match = option.dataset.search.includes(keyword);
            option.hidden = !match;
            if (match) visible += 1;
        });
        const empty = form.querySelector('[data-ingredient-empty]');
        if (empty) empty.hidden = visible > 0;
        ingredientFeedback.textContent = '';
    });

    form.addEventListener('click', event => {
        const add = event.target.closest('[data-add]');
        const remove = event.target.closest('[data-remove]');
        const ingredientAdd = event.target.closest('[data-ingredient-add]');
        const ingredientConfirm = event.target.closest('[data-ingredient-confirm]');
        const ingredientEdit = event.target.closest('[data-ingredient-edit]');
        if (ingredientAdd) {
            addIngredient(ingredientAdd);
            return;
        }
        if (ingredientConfirm) {
            confirmIngredientRow(ingredientConfirm.closest('[data-row]'));
            return;
        }
        if (ingredientEdit) {
            const row = ingredientEdit.closest('[data-row]');
            setIngredientMode(row, false);
            ingredientFeedback.textContent = 'Bạn đang sửa nguyên liệu đã chọn.';
            row.querySelector('input[name$="[quantity]"]')?.focus();
            return;
        }
        if (add) {
            const container = form.querySelector(`[data-rows="${add.dataset.add}"]`);
            if (container.children.length >= Number(container.dataset.max)) return;
            container.append(document.querySelector(`#${add.dataset.add}-template`).content.cloneNode(true));
            reindex(container);
            container.lastElementChild.querySelector('input, select, textarea').focus();
        }
        if (remove) {
            const container = remove.closest('[data-rows]');
            if (container.dataset.rows === 'steps' && container.children.length <= 1) return;
            remove.closest('[data-row]').remove();
            reindex(container);
            if (container.dataset.rows === 'ingredients') ingredientFeedback.textContent = '';
        }
    });
    form.addEventListener('change', event => {
        if (!event.target.matches('[data-ingredient]')) return;
        const unit = event.target.closest('[data-row]').querySelector('[data-unit-select]');
        if (!unit.value) unit.value = event.target.selectedOptions[0]?.dataset.unit || '';
        updateIngredientState();
    });

    const fieldLabel = field => {
        const label = field.id ? form.querySelector(`label[for="${CSS.escape(field.id)}"]`) : null;
        return label?.textContent.trim().replace(/\s*\(.*\)$/, '').toLocaleLowerCase('vi') || 'trường này';
    };

    const validationMessage = field => {
        const label = fieldLabel(field);
        if (field.validity.valueMissing) return `Vui lòng nhập hoặc chọn ${label}.`;
        if (field.validity.typeMismatch) return `${label} chưa đúng định dạng.`;
        if (field.validity.tooShort) return `${label} cần ít nhất ${field.minLength} ký tự.`;
        if (field.validity.tooLong) return `${label} không được vượt quá ${field.maxLength} ký tự.`;
        if (field.validity.rangeUnderflow) return `${label} phải từ ${field.min} trở lên.`;
        if (field.validity.rangeOverflow) return `${label} không được lớn hơn ${field.max}.`;
        if (field.validity.stepMismatch || field.validity.badInput) return `${label} chưa phải giá trị hợp lệ.`;
        return `Vui lòng kiểm tra lại ${label}.`;
    };

    const clearClientError = field => {
        field.removeAttribute('aria-invalid');
        const key = field.dataset.clientErrorKey;
        if (key) form.querySelector(`[data-client-error="${CSS.escape(key)}"]`)?.remove();
        delete field.dataset.clientErrorKey;
    };

    const setFieldError = (field, message) => {
        clearClientError(field);
        const key = `client-${Math.random().toString(36).slice(2)}`;
        const error = document.createElement('p');
        error.className = 'field-error client-error';
        error.dataset.clientError = key;
        error.id = `${key}-error`;
        error.setAttribute('role', 'alert');
        error.textContent = message;
        field.dataset.clientErrorKey = key;
        field.setAttribute('aria-invalid', 'true');
        field.insertAdjacentElement('afterend', error);
    };

    const setSectionMessage = (sectionName, message) => {
        const section = form.querySelector(`[data-form-section="${sectionName}"]`);
        if (!section) return null;
        const error = document.createElement('p');
        error.className = 'field-error client-error section-message';
        error.setAttribute('role', 'alert');
        error.textContent = message;
        section.querySelector('.section-heading')?.insertAdjacentElement('afterend', error);
        section.classList.add('has-error');
        return section;
    };

    const validateForm = () => {
        form.querySelectorAll('.client-error').forEach(error => error.remove());
        form.querySelectorAll('[aria-invalid="true"]').forEach(field => {
            if (!field.closest('.field')?.querySelector('.field-error:not(.client-error)')) field.removeAttribute('aria-invalid');
        });
        let firstInvalid = null;
        form.querySelectorAll('input[required], select[required], textarea[required]').forEach(field => {
            field.setCustomValidity('');
            if (field.checkValidity()) return;
            setFieldError(field, validationMessage(field));
            firstInvalid ||= field;
        });

        const prep = form.querySelector('#prepTime');
        const cook = form.querySelector('#cookTime');
        if (prep?.value !== '' && cook?.value !== '' && Number(prep.value) + Number(cook.value) <= 0) {
            setFieldError(cook, 'Tổng thời gian chuẩn bị và nấu phải lớn hơn 0.');
            firstInvalid ||= cook;
        }

        const ingredientSelects = [...form.querySelectorAll('[data-rows="ingredients"] [data-ingredient]')];
        if (!ingredientSelects.length) {
            const section = setSectionMessage('ingredients', 'Hãy thêm ít nhất một nguyên liệu.');
            firstInvalid ||= ingredientSearch || section;
        }
        const selectedIds = ingredientSelects.map(select => select.value).filter(Boolean);
        const duplicate = selectedIds.find((id, index) => selectedIds.indexOf(id) !== index);
        if (duplicate) {
            const duplicateField = ingredientSelects.find((select, index) => select.value === duplicate && index !== selectedIds.indexOf(duplicate));
            setFieldError(duplicateField, 'Nguyên liệu này đã được thêm trước đó.');
            firstInvalid ||= duplicateField;
        }
        const unconfirmedRow = [...form.querySelectorAll('[data-rows="ingredients"] [data-row]')]
            .find(row => row.dataset.confirmed !== 'true');
        if (unconfirmedRow) {
            setSectionMessage('ingredients', 'Hãy xác nhận từng nguyên liệu trước khi đăng công thức.');
            firstInvalid ||= unconfirmedRow.querySelector('[data-ingredient-confirm]');
        }

        const image = form.querySelector('#image');
        if (image?.files[0]) {
            const file = image.files[0];
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                setFieldError(image, 'Chỉ chấp nhận ảnh JPEG, PNG hoặc WebP.');
                firstInvalid ||= image;
            } else if (file.size > 2 * 1024 * 1024) {
                setFieldError(image, 'Ảnh không được lớn hơn 2 MB.');
                firstInvalid ||= image;
            }
        }

        updateSectionErrors();
        return firstInvalid;
    };

    form.addEventListener('input', event => {
        const field = event.target.closest('input, select, textarea');
        if (!field || !field.dataset.clientErrorKey) return;
        field.setCustomValidity('');
        if (field.checkValidity()) {
            clearClientError(field);
            updateSectionErrors();
        }
    });

    form.addEventListener('submit', event => {
        form.querySelectorAll('[data-rows]').forEach(reindex);
        const firstInvalid = validateForm();
        if (!firstInvalid) {
            form.setAttribute('aria-busy', 'true');
            const submit = form.querySelector('button[type="submit"]');
            if (submit) {
                submit.disabled = true;
                submit.textContent = 'Đang lưu…';
            }
            return;
        }
        event.preventDefault();
        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
        firstInvalid.focus?.({ preventScroll: true });
    });
    form.querySelectorAll('[data-rows]').forEach(reindex);
    form.querySelectorAll('[data-rows="ingredients"] [data-row]').forEach(row => syncIngredientSummary(row));
    updateIngredientState();
    updateSectionErrors();
}
