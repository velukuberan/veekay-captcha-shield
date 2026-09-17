<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Forms\Comments;

use WpCaptchaShield\Domain\Configuration\EffectiveCaptchaProvider;
use WpCaptchaShield\Domain\Configuration\EffectiveCaptchaProviderResolver;
use WpCaptchaShield\Domain\Configuration\FormCaptchaSetting;
use WpCaptchaShield\Domain\Verification\CaptchaVerificationRequest;
use WpCaptchaShield\Domain\Verification\VerificationResult;
use WpCaptchaShield\WordPress\Bootstrap\CaptchaServiceFactory;
use WpCaptchaShield\WordPress\Bootstrap\Configuration\CaptchaProviderConfigurationFactory;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidgetContext;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidgetRenderer;
use WpCaptchaShield\WordPress\Forms\SupportedForms;
use WpCaptchaShield\WordPress\Settings\PluginSettings;
use WpCaptchaShield\WordPress\Settings\SettingsRepository;

final class CommentFormIntegration
{
    private const CAPTCHA_ACTION = 'wordpress_comment';

    private const FORM_ID = 'commentform';

    private ?PluginSettings $settings = null;

    /**
     * @param list<string> $excludedPostTypes
     */
    public function __construct(
        private readonly SettingsRepository $repository,
        private readonly EffectiveCaptchaProviderResolver $providerResolver,
        private readonly CaptchaProviderConfigurationFactory $configurationFactory,
        private readonly CaptchaServiceFactory $serviceFactory,
        private readonly CaptchaWidgetRenderer $widgetRenderer,
        private readonly array $excludedPostTypes = [],
    ) {
    }

    public function enqueue(): void
    {
        if (!$this->isCommentFormPage()) {
            return;
        }

        $effectiveProvider = $this->effectiveProvider();

        if ($effectiveProvider->isDisabled()) {
            return;
        }

        $this->widgetRenderer->enqueue(
            $effectiveProvider,
            $this->widgetContext(),
            $this->settings(),
        );
    }

    public function addWidgetToSubmitField(string $submitField): string
    {
        if ($this->isExcludedPostType()) {
            return $submitField;
        }

        $effectiveProvider = $this->effectiveProvider();

        if ($effectiveProvider->isDisabled()) {
            return $submitField;
        }

        ob_start();

        $this->widgetRenderer->render(
            $effectiveProvider,
            $this->widgetContext(),
            $this->settings(),
        );

        $widget = ob_get_clean();

        return ($widget === false ? '' : $widget) . $submitField;
    }

    public function validate(int $commentPostId): void
    {
        if ($this->isExcludedPostType($commentPostId)) {
            return;
        }

        $effectiveProvider = $this->effectiveProvider();

        if ($effectiveProvider->isDisabled()) {
            return;
        }

        $result = $this->serviceFactory
            ->create(
                $this->configurationFactory->create($this->settings()),
            )
            ->verify(
                $effectiveProvider,
                new CaptchaVerificationRequest(
                    $this->submittedToken($effectiveProvider),
                    $this->serverValue('REMOTE_ADDR'),
                    $this->serverValue('HTTP_USER_AGENT'),
                    self::CAPTCHA_ACTION,
                ),
            );

        if ($result->isSuccessful()) {
            return;
        }

        wp_die(
            esc_html($this->visitorMessage($result)),
            esc_html__(
                'Comment submission blocked',
                'veekay-captcha-shield',
            ),
            [
                'response' => 403,
                'back_link' => true,
            ],
        );
    }

    private function widgetContext(): CaptchaWidgetContext
    {
        return new CaptchaWidgetContext(
            self::CAPTCHA_ACTION,
            self::FORM_ID,
        );
    }

    private function settings(): PluginSettings
    {
        return $this->settings ??= $this->repository->load();
    }

    private function effectiveProvider(): EffectiveCaptchaProvider
    {
        $settings = $this->settings();
        $formSetting = $settings->formSettings()[SupportedForms::WORDPRESS_COMMENTS]
            ?? FormCaptchaSetting::useDefault();

        return $this->providerResolver->resolve(
            $settings->globalSetting(),
            $formSetting,
        );
    }

    /**
     * WordPress core does not include a nonce in the native comment form.
     * The provider token is read only during native comment submission.
     */
    // phpcs:disable WordPress.Security.NonceVerification.Missing

    private function submittedToken(
        EffectiveCaptchaProvider $effectiveProvider,
    ): string {
        $field = $this->widgetRenderer->tokenFieldName(
            $effectiveProvider,
            $this->settings(),
        );

        if (
            $field === ''
            || !isset($_POST[$field])
            || !is_string($_POST[$field])
        ) {
            return '';
        }

        return sanitize_text_field(
            wp_unslash($_POST[$field]),
        );
    }

    // phpcs:enable WordPress.Security.NonceVerification.Missing

    private function serverValue(string $key): ?string
    {
        if (!isset($_SERVER[$key]) || !is_string($_SERVER[$key])) {
            return null;
        }

        return sanitize_text_field(wp_unslash($_SERVER[$key]));
    }

    private function isCommentFormPage(): bool
    {
        if (!is_singular() || !comments_open()) {
            return false;
        }

        $postType = get_post_type();

        return is_string($postType)
            && !in_array($postType, $this->excludedPostTypes, true)
            && post_type_supports($postType, 'comments');
    }

    private function isExcludedPostType(?int $postId = null): bool
    {
        $postType = get_post_type($postId);

        return is_string($postType)
            && in_array($postType, $this->excludedPostTypes, true);
    }

    private function visitorMessage(VerificationResult $result): string
    {
        if ($result->isUnavailable()) {
            return __(
                'CAPTCHA verification is temporarily unavailable. Please try again.',
                'veekay-captcha-shield',
            );
        }

        if ($result->isMisconfigured()) {
            return __(
                'CAPTCHA verification could not be completed. Please contact the site administrator.',
                'veekay-captcha-shield',
            );
        }

        return __(
            'CAPTCHA verification failed. Please try again.',
            'veekay-captcha-shield',
        );
    }
}
