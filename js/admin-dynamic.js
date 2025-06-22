/*document.addEventListener('DOMContentLoaded', function () {
    const select = document.getElementById('salt_translator_select');
    const textarea = document.getElementById('salt_api_keys_textarea');
    const apiRow = document.querySelector('.api_keys_row');
    const openaiSettings = document.querySelector('.openai_settings');
    const keyStore = window.saltTranslatorKeys || {};

    function toggleFields() {
        const current = select.dataset.prev || '';
        if (current && textarea) {
            keyStore[current] = textarea.value.trim();
        }

        const newVal = select.value;
        if (textarea) {
            textarea.value = keyStore[newVal] || '';
        }

        apiRow.style.display = newVal ? 'block' : 'none';
        if (openaiSettings) openaiSettings.style.display = newVal === 'openai' ? 'block' : 'none';

        select.dataset.prev = newVal;
    }

    select.addEventListener('change', toggleFields);
    select.dataset.prev = select.value;
    toggleFields();
});
*/

document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form');
    const translatorSelect = document.getElementById('salt_translator_select');

    if (!form || !translatorSelect) return;

    form.addEventListener('submit', function (e) {
        if (!translatorSelect.value) {
            e.preventDefault();
            alert('Lütfen bir çeviri hizmeti (Translator) seçin.');
            translatorSelect.focus();
        }
    });
});


document.addEventListener('DOMContentLoaded', function () {
    const select = document.getElementById('salt_translator_select');
    const apiTextareas = document.querySelectorAll('.salt-api-key-textarea');
    const apiRow = document.querySelector('.api_keys_row');
    const openaiSettings = document.querySelector('.openai_settings');
    const keyStore = window.saltTranslatorKeys || {};

    function toggleFields() {
        const current = select.dataset.prev || '';
        const textareas = document.querySelectorAll('.salt-api-key-textarea');

        // Mevcut textarea verisini store et
        if (current) {
            const currentTextarea = document.querySelector(`[data-translator="${current}"]`);
            if (currentTextarea) {
                keyStore[current] = currentTextarea.value.trim();
            }
        }

        const newVal = select.value;
        select.dataset.prev = newVal;

        // Tüm textarea'ları önce gizle
        textareas.forEach(el => el.style.display = 'none');

        // Hedef textarea'yı göster ve value ata
        const targetTextarea = document.querySelector(`[data-translator="${newVal}"]`);
        if (targetTextarea) {
            targetTextarea.style.display = 'block';

            // Zorla yeniden yaz
            setTimeout(() => {
                targetTextarea.value = (keyStore[newVal] || targetTextarea.defaultValue || '').trim();
            }, 10); // küçük delay render garantisi sağlar
        }

        // Alanları göster/gizle
        apiRow.style.display = newVal ? 'block' : 'none';
        if (openaiSettings) openaiSettings.style.display = newVal === 'openai' ? 'block' : 'none';
    }

    select.addEventListener('change', toggleFields);
    toggleFields();
});




document.getElementById('salt_translator_select').addEventListener('change', function () {
    const selected = this.value;
    const textarea = document.getElementById('salt_api_keys_textarea');

    if (!textarea) return;

    const baseName = 'salt_ai_translator_settings[api_keys]';
    textarea.setAttribute('name', `${baseName}[${selected}]`);
});

jQuery(function($) {
    setTimeout(function () {
        console.log('Başlatılıyor: select2-ajax-posts & terms');

        $('.select2-ajax-posts').select2({
            ajax: {
                url: saltTranslator.ajax_url,
                dataType: 'json',
                delay: 250,
                method: "POST",
                data: function (params) {
                    return {
                        action: 'salt_autocomplete_posts',
                        nonce: saltTranslator.nonce,
                        q: params.term,
                        page: params.page || 1
                    };
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.items,
                        pagination: {
                            more: data.has_more
                        }
                    };
                },
                cache: true
            },
            minimumInputLength: 2,
            width: '100%'
        });

        $('.select2-ajax-terms').select2({
            ajax: {
                url: saltTranslator.ajax_url,
                dataType: 'json',
                delay: 250,
                method: "POST",
                data: function (params) {
                    return {
                        action: 'salt_autocomplete_terms',
                        nonce: saltTranslator.nonce,
                        q: params.term,
                        page: params.page || 1
                    };
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.items,
                        pagination: {
                            more: data.has_more
                        }
                    };
                },
                cache: true
            },
            minimumInputLength: 2,
            width: '100%'
        });
        
    }, 300); // DOM otursun
});
