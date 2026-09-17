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
        return __('Cloudflare Turnstile', 'veekay-captcha-shield');
    }

    public function showsSubmitButton(): bool
    {
        return true;
    }

    public function render(PluginSettings $settings): void
    {
        $turnstile = $settings->turnstile();
        ?>
        <h2><?php echo esc_html__('Cloudflare Turnstile', 'veekay-captcha-shield'); ?></h2>

        <p class="description">
            <?php echo esc_html__(
                'The widget mode is configured in your Cloudflare Turnstile dashboard.',
                'veekay-captcha-shield',
            ); ?>
        </p>

        <p class="description">
            <?php echo esc_html__(
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'Select the same mode here as the mode configured for this site key. Changing this setting does not change the widget mode in Cloudflare.',
                'veekay-captcha-shield',
            ); ?>
        </p>

        <table class="form-table" role="presentation">
            <?php
            $this->fields->renderTextField(
                'turnstile-site-key',
                __('Site key', 'veekay-captcha-shield'),
                'wp_captcha_shield[turnstile][site_key]',
                $turnstile->siteKey(),
                help: __(
                    'Public site key supplied by Cloudflare. It is used in the browser to render Turnstile.',
                    'veekay-captcha-shield',
                ),
            );

            $this->fields->renderSecretField(
                'turnstile-secret-key',
                __('Secret key', 'veekay-captcha-shield'),
                'wp_captcha_shield[turnstile][secret_key]',
                $turnstile->secretKey() !== '',
                __(
                    'Private key supplied by Cloudflare and used only on the server to verify CAPTCHA tokens.',
                    'veekay-captcha-shield',
                ),
            );

            $this->fields->renderSelectField(
                'turnstile-mode',
                __('Mode', 'veekay-captcha-shield'),
                'wp_captcha_shield[turnstile][mode]',
                $turnstile->mode()->value,
                [
                    'managed' => __('Managed', 'veekay-captcha-shield'),
                    'non_interactive' => __('Non-Interactive', 'veekay-captcha-shield'),
                    'invisible' => __('Invisible', 'veekay-captcha-shield'),
                ],
                __(
                    // phpcs:ignore Generic.Files.LineLength.TooLong
                    'Must match the widget mode configured for this site key in Cloudflare. Managed is recommended for most sites.',
                    'veekay-captcha-shield',
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
                                            'veekay-captcha-shield',
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
