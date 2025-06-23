<?php
    $queue_status = $this->container->get("manager")->check_process_queue("post");
    $viewer_display = "none";
    $viewer_loading_class = "salt-spinner";
    $info_display = "none";
    $buttons_display = "block";
    $progress_display = "none";
    if($queue_status == "processing"){
        $viewer_display = "flex";
        $viewer_loading_class = "";
        $info_display = "flex";
        $buttons_display = "none";
        $progress_display = "flex";
    }
?>
<div class="salt-container">

    <header class="salt-header">
        <div class="salt-logo">A</div>
        <div class="salt-title">
            <h1><?php _e('SALT AI TRANSLATOR', 'salt-ai-translator'); ?></h1>
            <p><?php _e('Automatic Multilingual Translation System — AI-Powered', 'salt-ai-translator'); ?></p>
        </div>
    </header>

    <div class="wrap">

        <h1 class="salt-section-header mb-4" style="margin-bottom:30px;">
            <strong><?php _e('Post Translation', 'salt-ai-translator'); ?></strong>
        </h1>
        
        <?php
        if($queue_status != "processing"){
        ?>
        <div id="salt-language-selector" class="salt-form-group mb-4">
            <label class="salt-label"><strong><?php _e('Select Target Language', 'salt-ai-translator'); ?></strong></label>
            <div class="salt-input-group" style="max-width: 600px;">
                <select id="salt-translate-lang" class="salt-select">
                    <option value=""><?php _e('Select a language to translate', 'salt-ai-translator'); ?></option>
                    <?php foreach ($this->languages as $code => $label): ?>
                        <option value="<?= esc_attr($code) ?>"><?= esc_html($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <button id="salt-check-translation" class="salt-button btn-primary"> <?php _e('Check Translation', 'salt-ai-translator'); ?></button>
            </div>
        </div>
        <?php
        }
        ?>

        <div id="salt-translation-viewer" class="salt-translation-viewer mb-4 <?= $viewer_loading_class ?>" style="display:<?= $viewer_display?>;">

            <div id="salt-translation-info" style="display:<?= $info_display?>;" class="salt-translation-info mt-4">

                <?php 
                $queue_percent = 0;
                if($queue_status == "processing"){
                    $queue_status_data = $this->container->get("manager")->get_queue_status("post");
                    $queue_started_at  = date('d.m.Y H:i:s', $queue_status_data["started_at"]);
                    $queue_initial_total = max(1, $queue_status_data['initial_total']); // 0’a bölünmeyi engeller
                    $queue_completed   = $queue_status_data['completed'];
                    $queue_percent     = round(($queue_completed / $queue_initial_total) * 100);
                }
                ?>

                <p id="salt-translation-status" class="salt-translation-status mb-2">
                    <strong>
                        <?php
                        printf(
                            __('%1$d/%2$d translated', 'salt-ai-translator'),
                            $queue_completed,
                            $queue_initial_total
                        );
                        ?>
                    </strong>
                </p>

                <div class="salt-form-group mb-3" style="width:100%;max-width: 600px;" >
                    <div id="salt-translation-progress" class="salt-progress flex-grow-1" style="height: 28px;<?= $progress_display ?>;">
                        <div class="salt-progress-bar salt-progress-bar-animated" role="progressbar" style="width:<?= $queue_percent ?>%;"><?= $queue_percent ?>%</div>
                    </div>
                </div>

                <div id="salt-start-buttons" class="start-buttons" style="display:<?= $buttons_display?>;">
                    <button id="salt-start-translation" class="salt-button bg-primary" style="min-width:250px;">
                        <strong><?php _e('Translate Now', 'salt-ai-translator'); ?></strong>
                        <span><?php _e('Instant AJAX translation', 'salt-ai-translator'); ?></span>
                    </button>
                    <button id="salt-start-cron-translation" class="salt-button bg-primary" style="min-width:250px;">
                        <strong><?php _e('Add to Queue', 'salt-ai-translator'); ?></strong>
                        <span><?php _e('Background CRON translation', 'salt-ai-translator'); ?></span>
                    </button>
                </div>

                <table id="results-ui" class="results-ui table" style="display:none;">
                    <tbody>

                    </tbody>
                </table>

            </div>
            
        </div>

        <?php 
        if(!empty($queue_status)){
            $queue_total_title = __('Total', 'salt-ai-translator');
            $queue_language_title = __('Language', 'salt-ai-translator');
            $queue_started_at_title = __('Started at', 'salt-ai-translator');
            $queue_completed_at_title = __('Completed at', 'salt-ai-translator');
            $queue_processing_time_title = __('Processing Time', 'salt-ai-translator');
            $queue_status_title = __('Status', 'salt-ai-translator');

            if($queue_status == "idle"){
                $queue_initial_total = "-";
                $queue_language = "-";
                $queue_started_at = "-";
                $queue_completed_at = "-";
                $queue_duration_str = "-";
                $queue_status_str = "<strong class='text-primary'>" . __("Awaiting first translation task", "salt-ai-translator") . "</strong>";     
            }else{
                $queue_status_data = $this->container->get("manager")->get_queue_status("post");
                $queue_initial_total = sprintf(
                    _n('%d post', '%d posts', $queue_status_data["initial_total"], 'salt-ai-translator'),
                    $queue_status_data["initial_total"]
                );
                $queue_language = $this->container->get("integration")->get_language_label($queue_status_data["lang"]);
                $queue_started_at = get_date_from_gmt( date( 'Y-m-d H:i:s', $queue_status_data["started_at"] ), 'd.m.Y H:i:s' );
                $queue_completed_at = "-";
                $queue_duration_str = "-";
                $queue_status_str = "<strong class='text-success'>" . __("Processing", "salt-ai-translator") . "</strong>";                
            }

            if($queue_status == "done"){
                $queue_completed_at = get_date_from_gmt( date( 'Y-m-d H:i:s', $queue_status_data["completed_at"] ), 'd.m.Y H:i:s' );
                $queue_duration = $queue_status_data["completed_at"] - $queue_status_data["started_at"];
                $queue_hours   = floor($queue_duration / 3600);
                $queue_minutes = floor(($queue_duration % 3600) / 60);
                $queue_seconds = $queue_duration % 60;
                $queue_duration_parts = [];
                if ($queue_hours > 0) {
                    $queue_duration_parts[] = sprintf(
                        _n('%d hour', '%d hours', $queue_hours, 'salt-ai-translator'),
                        $queue_hours
                    );
                }
                if ($queue_minutes > 0) {
                    $queue_duration_parts[] = sprintf(
                        _n('%d minute', '%d minutes', $queue_minutes, 'salt-ai-translator'),
                        $queue_minutes
                    );
                }
                if ($queue_seconds > 0 || empty($queue_duration_parts)) {
                    $queue_duration_parts[] = sprintf(
                        _n('%d second', '%d seconds', $queue_seconds, 'salt-ai-translator'),
                        $queue_seconds
                    );
                }
                $queue_duration_str = implode(' ', $queue_duration_parts);     
                $queue_status_str = "<strong class='text-success'>" . __("Done", "salt-ai-translator") . "</strong>";           
            }

            ?>
            <h3 style="margin-top:40px;"><?php _e("Last Scheduled Task", 'salt-ai-translator');?></h3>
            <table class="salt-table">
                    <thead>
                        <tr>
                            <td>
                               <?= $queue_total_title ?>
                            </td>
                            <td>
                               <?= $queue_language_title ?>
                            </td>
                            <td>
                               <?= $queue_started_at_title ?>
                            </td>
                            <td>
                               <?= $queue_completed_at_title ?>
                            </td>
                            <td>
                               <?= $queue_processing_time_title ?>
                            </td>
                            <td>
                               <?= $queue_status_title ?>
                            </td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td id="queue_initial_total">
                                <?= $queue_initial_total ?>
                            </td>
                            <td id="queue_language">
                                <?= $queue_language ?>
                            </td>
                            <td id="queue_started_at">
                                <?= $queue_started_at ?>
                            </td>
                            <td id="queue_completed_at">
                                <?= $queue_completed_at ?>
                            </td>
                            <td id="queue_processing_time">
                                <?= $queue_duration_str ?>
                            </td>
                            <td id="queue_status">
                                <?= $queue_status_str ?>
                            </td>
                        </tr>
                    </tbody>
            </table>
        <?php
        }
        ?>

    </div>
</div>
