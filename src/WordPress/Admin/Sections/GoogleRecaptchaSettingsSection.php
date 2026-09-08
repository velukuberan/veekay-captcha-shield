<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Admin\Sections;

use WpCaptchaShield\WordPress\Admin\SettingsFieldRenderer;
use WpCaptchaShield\WordPress\Settings\PluginSettings;

final class GoogleRecaptchaSettingsSection implements SettingsTabSection
{
    public function __construct(
        private readonly SettingsFieldRenderer $fields,
    ) {
    }

    public function slug(): string
    {
        return 'google';
    }

    public function label(): string
    {
        return __('Google reCAPTCHA', 'captcha-security-shield');
    }

    public function showsSubmitButton(): bool
    {
        return true;
    }

    public function render(PluginSettings $settings): void
    {
        $google = $settings->googleRecaptcha();
        ?>
        <h2><?php echo esc_html__('Google reCAPTCHA', 'captcha-security-shield'); ?></h2>
        <table class="form-table" role="presentation">
            <?php
            $this->fields->renderTextField(
                'google-project-id',
                __('Project ID', 'captcha-security-shield'),
                'wp_captcha_shield[google_recaptcha][project_id]',
                $google->projectId(),
                help: __(
                    'Google Cloud project containing the reCAPTCHA Enterprise configuration.',
                    'captcha-security-shield',
                ),
            );

            $this->fields->renderSecretField(
                'google-api-key',
                __('API key', 'captcha-security-shield'),
                'wp_captcha_shield[google_recaptcha][api_key]',
                $google->apiKey() !== '',
                __(
                    'Server-side Google Cloud API key used to create reCAPTCHA assessments.',
                    'captcha-security-shield',
                ),
            );

            $this->fields->renderTextField(
                'google-site-key',
                __('Site key', 'captcha-security-shield'),
                'wp_captcha_shield[google_recaptcha][site_key]',
                $google->siteKey(),
                help: __(
                    'reCAPTCHA Enterprise site key used by protected forms in the browser.',
                    'captcha-security-shield',
                ),
            );

            $this->fields->renderSelectField(
                'google-mode',
                __('Mode', 'captcha-security-shield'),
                'wp_captcha_shield[google_recaptcha][mode]',
                $google->mode()->value,
                [
                    'score_based' => __('Score-based', 'captcha-security-shield'),
                    'checkbox' => __('Checkbox', 'captcha-security-shield'),
                    'invisible' => __('Invisible', 'captcha-security-shield'),
                ],
                __(
                    'Determines how reCAPTCHA interacts with visitors. Score-based is recommended for most sites.',
                    'captcha-security-shield',
                ),
            );

            $this->fields->renderTextField(
                'google-minimum-score',
                __('Minimum score', 'captcha-security-shield'),
                'wp_captcha_shield[google_recaptcha][minimum_score]',
                (string) $google->minimumScore(),
                'number',
                '0',
                '1',
                '0.1',
                __(
                    'Minimum acceptable score for score-based verification. '
                    . 'Higher values are stricter. Use a value between 0 '
                    . 'and 1.',
                    'captcha-security-shield',
                ),
            );
            ?>
        </table>
        <?php
    }
}
