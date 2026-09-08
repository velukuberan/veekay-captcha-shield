<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Admin\Sections;

use WpCaptchaShield\Domain\Environment\EnvironmentCompatibility;
use WpCaptchaShield\WordPress\Settings\PluginSettings;

final class StatusSection implements SettingsTabSection
{
    private const MINIMUM_PHP_VERSION = '8.1.0';
    private const MINIMUM_WORDPRESS_VERSION = '6.7.0';
    private const MINIMUM_WOOCOMMERCE_VERSION = '10.1.0';

    public function __construct(
        private readonly EnvironmentCompatibility $compatibility,
    ) {
    }

    public function slug(): string
    {
        return 'status';
    }

    public function label(): string
    {
        return __('Status', 'captcha-security-shield');
    }

    public function showsSubmitButton(): bool
    {
        return false;
    }

    public function render(PluginSettings $settings): void
    {
        unset($settings);

        $wordpressVersion = get_bloginfo('version');
        $wooCommerceVersion = defined('WC_VERSION') ? WC_VERSION : null;
        ?>
        <h2><?php echo esc_html__('Status and compatibility', 'captcha-security-shield'); ?></h2>
        <p class="description">
            <?php echo esc_html__(
                'Compare the current environment with the minimum versions supported by Captcha Security Shield.',
                'captcha-security-shield',
            ); ?>
        </p>
        <table class="widefat striped wp-captcha-shield-status-table">
            <thead>
                <tr>
                    <th scope="col"><?php echo esc_html__('Component', 'captcha-security-shield'); ?></th>
                    <th scope="col"><?php echo esc_html__('Minimum supported', 'captcha-security-shield'); ?></th>
                    <th scope="col"><?php echo esc_html__('Current', 'captcha-security-shield'); ?></th>
                    <th scope="col"><?php echo esc_html__('Status', 'captcha-security-shield'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $this->renderStatusRow(
                    __('PHP', 'captcha-security-shield'),
                    self::MINIMUM_PHP_VERSION,
                    PHP_VERSION,
                    $this->compatibility->isAtLeast(PHP_VERSION, self::MINIMUM_PHP_VERSION),
                );
                $this->renderStatusRow(
                    __('WordPress', 'captcha-security-shield'),
                    self::MINIMUM_WORDPRESS_VERSION,
                    $wordpressVersion,
                    $this->compatibility->isAtLeast($wordpressVersion, self::MINIMUM_WORDPRESS_VERSION),
                );

                if ($wooCommerceVersion === null) {
                    $this->renderStatusRow(
                        __('WooCommerce', 'captcha-security-shield'),
                        self::MINIMUM_WOOCOMMERCE_VERSION,
                        __('Not active', 'captcha-security-shield'),
                        null,
                    );
                } else {
                    $this->renderStatusRow(
                        __('WooCommerce', 'captcha-security-shield'),
                        self::MINIMUM_WOOCOMMERCE_VERSION,
                        $wooCommerceVersion,
                        $this->compatibility->isAtLeast($wooCommerceVersion, self::MINIMUM_WOOCOMMERCE_VERSION),
                    );
                }
                ?>
            </tbody>
        </table>
        <p class="description">
            <?php echo esc_html__(
                'WooCommerce is optional. WordPress form protection remains available when WooCommerce is not active.',
                'captcha-security-shield',
            ); ?>
        </p>

        <p class="description">
            <?php echo esc_html__(
                'WooCommerce versions below 10.1.0 are outside the supported compatibility range.',
                'captcha-security-shield',
            ); ?>
        </p>
        <?php
    }

    private function renderStatusRow(
        string $component,
        string $minimumVersion,
        string $currentVersion,
        ?bool $compatible,
    ): void {
        $status = __('Optional', 'captcha-security-shield');
        $statusClass = 'is-optional';

        if ($compatible === true) {
            $status = __('Compatible', 'captcha-security-shield');
            $statusClass = 'is-compatible';
        } elseif ($compatible === false) {
            $status = __('Unsupported', 'captcha-security-shield');
            $statusClass = 'is-unsupported';
        }
        ?>
        <tr>
            <th scope="row"><?php echo esc_html($component); ?></th>
            <td><?php echo esc_html($minimumVersion); ?></td>
            <td><?php echo esc_html($currentVersion); ?></td>
            <td>
                <span class="wp-captcha-shield-status <?php echo esc_attr($statusClass); ?>">
                    <?php echo esc_html($status); ?>
                </span>
            </td>
        </tr>
        <?php
    }
}
