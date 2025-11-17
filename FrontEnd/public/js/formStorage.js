// formStorage.js
// Lógica modular para persistencia y restauración del formulario

const FORM_STORAGE_KEY = 'cv_form_state_v1';

const formStorage = {
    debounce(fn, wait) {
        let t = null;
        return function () {
            const ctx = this, args = arguments;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(ctx, args); }, wait);
        };
    },
    saveState(state) {
        try {
            localStorage.setItem(FORM_STORAGE_KEY, JSON.stringify(state));
        } catch (e) {
            console.warn('Error guardando estado en storage', e);
        }
    },
    loadState() {
        try {
            const raw = localStorage.getItem(FORM_STORAGE_KEY);
            if (!raw) return null;
            return JSON.parse(raw);
        } catch (e) {
            console.warn('Error leyendo estado del storage', e);
            return null;
        }
    },
    clearState() {
        try {
            localStorage.removeItem(FORM_STORAGE_KEY);
        } catch (e) { console.warn(e); }
    },
    getAllInputsState() {
        const data = {};
        $("input, select, textarea").not("input[type='file']").each(function () {
            const name = $(this).attr('name');
            if (!name) return;
            if (!data[name]) data[name] = [];
            if ($(this).is(':radio')) {
                if ($(this).is(':checked')) data[name].push($(this).val());
            } else {
                data[name].push($(this).val());
            }
        });
        return data;
    },
    restoreInputsFromState(stateSections) {
        if (!stateSections) return;
        Object.entries(stateSections).forEach(([name, values]) => {
            const $els = $(`[name='${name}']`);
            if ($els.length === 0) {
                // try with [] suffix
                const $els2 = $(`[name='${name}[]']`);
                if ($els2.length) {
                    $els2.val(values);
                    return;
                }
                return;
            }
            $els.each(function (i) {
                if ($(this).is(':radio')) {
                    if (values.includes($(this).val())) $(this).prop('checked', true).trigger('change');
                } else {
                    $(this).val(values[i] !== undefined ? values[i] : values[0]).trigger('change');
                }
            });
        });
    }
};

window.formStorage = formStorage;
