<?php
// Module content, loaded only for the pages that use it.
return [
            ['id' => 'dispatch', 'icon' => '↗', 'title' => SampleI18n::t('clear_path_dispatch'), 'text' => SampleI18n::t('urls_map_to_directories_controllers_and_entry_methods_multi'), 'note' => SampleI18n::t('parse_path_load_file_call_method')],
            ['id' => 'modes', 'icon' => '⌘', 'title' => SampleI18n::t('namespaced_controllers'), 'text' => SampleI18n::t('organize_controllers_with_namespaces_and_configure_namespace_prefixes'), 'note' => 'NAMESPACE'],
            ['id' => 'plugins', 'icon' => '◎', 'title' => SampleI18n::t('separate_core_and_plugins'), 'text' => SampleI18n::t('the_core_focuses_on_dispatch_load_templates_configuration_databases'), 'note' => SampleI18n::t('use_independently_compose_as_needed')],
            ['id' => 'cli', 'icon' => '›_', 'title' => SampleI18n::t('explicit_path_invocation'), 'text' => SampleI18n::t('run_controllers_from_the_cli_or_internal_calls_with'), 'note' => SampleI18n::t('cli_internal_calls')],
            ['id' => 'config', 'icon' => '≡', 'title' => SampleI18n::t('configurable_dispatch_rules'), 'text' => SampleI18n::t('customize_the_default_zone_page_entry_delimiter_namespace_and'), 'note' => SampleI18n::t('you_define_defaults_and_directory_structure')],
            ['id' => 'boundary', 'icon' => '◇', 'title' => SampleI18n::t('explicit_application_boundaries'), 'text' => SampleI18n::t('file_loading_validates_the_appdir_boundary_and_calls_only'), 'note' => SampleI18n::t('lightweight_core_explicit_control')],
        ];
