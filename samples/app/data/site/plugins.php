<?php
// Module content, loaded only for the pages that use it.
return [
            ['name' => 'Tpl', 'group' => SampleI18n::t('core'), 'type' => 'base', 'text' => SampleI18n::t('template_compilation_and_variable_assignment_with_conditions_loops_and'), 'need' => SampleI18n::t('requires_trusted_templates_and_a_writable_compilation_directory')],
            ['name' => 'Config', 'group' => SampleI18n::t('core'), 'type' => 'base', 'text' => SampleI18n::t('parse_json_jsonc_configuration_into_objects_arrays_and_basic'), 'need' => SampleI18n::t('configuration_files_disable_caching_during_development')],
            ['name' => 'Route', 'group' => SampleI18n::t('core'), 'type' => 'base', 'text' => SampleI18n::t('php_json_custom_url_mappings_and_http_method_attributes'), 'need' => SampleI18n::t('register_url_rules_before_run_the_core_enforces_method')],
            ['name' => 'Log', 'group' => SampleI18n::t('core'), 'type' => 'base', 'text' => SampleI18n::t('leveled_logging_with_configurable_console_and_file_output'), 'need' => SampleI18n::t('directory_write_permission_for_file_logging')],
            ['name' => 'Db', 'group' => SampleI18n::t('data'), 'type' => 'data', 'text' => SampleI18n::t('database_table_operations_and_read_write_configuration_management'), 'need' => SampleI18n::t('database_service_driver_and_connection_configuration')],
            ['name' => 'Redis', 'group' => SampleI18n::t('data'), 'type' => 'data', 'text' => SampleI18n::t('redis_configuration_and_access_for_caching_and_other_data'), 'need' => SampleI18n::t('redis_php_extension_and_a_running_service')],
            ['name' => 'Http', 'group' => SampleI18n::t('extra'), 'type' => 'extra', 'text' => SampleI18n::t('http_requests_through_guzzle_with_timeouts_headers_and_parameters'), 'need' => SampleI18n::t('install_guzzle_with_composer_requires_network_access')],
            ['name' => 'Language', 'group' => SampleI18n::t('extra'), 'type' => 'extra', 'text' => SampleI18n::t('organize_translation_entries_and_language_files'), 'need' => SampleI18n::t('prepare_locale_resources')],
            ['name' => 'Captcha', 'group' => SampleI18n::t('extra'), 'type' => 'extra', 'text' => SampleI18n::t('generate_verification_images_using_gregwar_captcha'), 'need' => SampleI18n::t('composer_dependencies_gd_mbstring_fileinfo_and_sessions')],
            ['name' => 'Error', 'group' => SampleI18n::t('extra'), 'type' => 'extra', 'text' => SampleI18n::t('format_and_log_php_errors_and_exceptions'), 'need' => SampleI18n::t('configure_an_error_output_policy_for_your_environment')],
        ];
