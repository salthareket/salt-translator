<?php
?>
<div class="wrap">
    <h1><?php _e('Term Translation Check', 'salt-ai-translator'); ?></h1>
    <div class="mb-4">
        <label class="form-label"><strong><?php _e('Select Language', 'salt-ai-translator'); ?></strong></label>
        <div class="input-group" style="max-width: 600px;">
            <select id="salt-translate-lang" class="form-select">
                <option value="">Çevrilecek dili seçin</option>
                <?php
                foreach ($this->languages as $code => $label) {?>
                    <option value="<?php echo $code;?>"><?php echo $label;?></option>
                <?php
                }
                ?>
            </select>
            <button id="salt-check-translation" class="btn btn-primary">
                <?php _e('Translation Check', 'salt-ai-translator'); ?>
            </button>
        </div>
    </div>

    <div id="salt-translation-info" style="display:none;" class="mt-4">
        <p id="salt-translation-status" class="form-label fs-5 fw-bold mb-2">
            <strong><?php _e('0 terms are untranslated in the selected language.', 'salt-ai-translator'); ?></strong>
        </p>
        <div class="input-group mb-3" style="max-width: 600px;">
            <div class="progress flex-grow-1" style="height: 38px;">
                <div id="salt-translation-progress" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;">0%</div>
            </div>
            <button id="salt-start-translation" class="btn btn-success" style="display:none;">
                <?php _e('Start Translation', 'salt-ai-translator'); ?>
            </button>
        </div>
    </div>

    <div id="salt-translation-result" class="mt-5"></div>

</div>