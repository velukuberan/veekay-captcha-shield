<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Admin\Sections;

use WpCaptchaShield\Domain\Configuration\Provider\CloudflareTurnstileMode;
use WpCaptchaShield\WordPress\Admin\SettingsFieldRenderer;
use WpCaptchaShield\WordPress\Settings\PluginSettings;

final class TurnstileSettingsSection implements SettingsTabSection
{
    private const TURNSTILE_PRIVACY_ADDENDUM_URL =
        'https://www.cloudflare.com/turnstile-privacy-policy/';

    public function __construct(
        private readonly SettingsFieldRenderer $fields,
    ) {
    }

    public function slug(): string
    {
        return 'turnstile';
    }

    public function label(): string
    {
        return __('Cloudflare Turnstile', 'captcha-security-shield');
    }

    public function showsSubmitButton(): bool
    {
        return true;
    }

    public function render(PluginSettings $settings): void
    {
        $turnstile = $settings->turnstile();
        ?>
        <h2><?php echo esc_html__('Cloudflare Turnstile', 'captcha-security-shield'); ?></h2>

        <p class="description">
            <?php echo esc_html__(
                'The widget mode is configured in your Cloudflare Turnstile dashboard.',
                'captcha-security-shield',
            ); ?>
        </p>

        <p class="description">
            <?php echo esc_html__(
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'Select the same mode here as the mode configured for this site key. Changing this setting does not change the widget mode in Cloudflare.',
                'captcha-security-shield',
            ); ?>
        </p>

        <table class="form-table" role="presentation">
            <?php
            $this->fields->renderTextField(
                'turnstile-site-key',
                __('Site key', 'captcha-security-shield'),
                'wp_captcha_shield[turnstile][site_key]',
                $turnstile->siteKey(),
                help: __(
                    'Public site key supplied by Cloudflare. It is used in the browser to render Turnstile.',
                    'captcha-security-shield',
                ),
            );

            $this->fields->renderSecretField(
                'turnstile-secret-key',
                __('Secret key', 'captcha-security-shield'),
                'wp_captcha_shield[turnstile][secret_key]',
                $turnstile->secretKey() !== '',
                __(
                    'Private key supplied by Cloudflare and used only on the server to verify CAPTCHA tokens.',
                    'captcha-security-shield',
                ),
            );

            $this->fields->renderSelectField(
                'turnstile-mode',
                __('Mode', 'captcha-security-shield'),
                'wp_captcha_shield[turnstile][mode]',
                $turnstile->mode()->value,
                [
                    'managed' => __('Managed', 'captcha-security-shield'),
                    'non_interactive' => __('Non-Interactive', 'captcha-security-shield'),
                    'invisible' => __('Invisible', 'captcha-security-shield'),
                ],
                __(
                    // phpcs:ignore Generic.Files.LineLength.TooLong
                    'Must match the widget mode configured for this site key in Cloudflare. Managed is recommended for most sites.',
                    'captcha-security-shield',
                ),
            );
            ?>

            <?php if ($turnstile->mode() === CloudflareTurnstileMode::Invisible): ?>
                <tr>
                    <th scope="row"></th>
                    <td>
                        <div class="notice notice-warning inline">
                            <p>
                                <?php
                                printf(
                                    wp_kses(
                                        /* translators: %s: URL to the Cloudflare Turnstile Privacy Addendum. */
                                        __(
                                            // phpcs:ignore Generic.Files.LineLength.TooLong
                                            'Cloudflare requires websites using Invisible Turnstile to reference the <a href="%s" target="_blank" rel="noopener noreferrer">Turnstile Privacy Addendum</a> in their privacy policy.',
                                            'captcha-security-shield',
                                        ),
                                        [
                                            'a' => [
                                                'href' => true,
                                                'target' => true,
                                                'rel' => true,
                                            ],
                                        ],
                                    ),
                                    esc_url(self::TURNSTILE_PRIVACY_ADDENDUM_URL),
                                );
                                ?>
                            </p>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        </table>
        <?php
    }
}
