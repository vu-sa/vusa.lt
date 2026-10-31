<?php

namespace App\Services\MailPreview;

use Closure;
use Illuminate\Http\Response;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Markdown;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Traits\Localizable;
use Throwable;

class MailPreviewRenderer
{
    use Localizable;

    public const string CSP = "sandbox; default-src 'none'; img-src https: data:; style-src 'unsafe-inline'";

    public function subject(MailMessage|Mailable $mail): string
    {
        if (filled($mail->subject)) {
            return $mail->subject;
        }

        if ($mail instanceof Mailable) {
            if (method_exists($mail, 'envelope')) {
                return $mail->envelope()->subject ?? '';
            }
            if (method_exists($mail, 'build')) {
                $mail->build();
            }
        }

        return $mail->subject ?? '';
    }

    public function safely(Closure $render, string $locale): array
    {
        return $this->withLocale($locale, function () use ($render): array {
            try {
                return $render();
            } catch (Throwable $exception) {
                report($exception);

                return [
                    'subject' => __('mail_previews.failed'),
                    'html' => '<!doctype html><html lang="'.e(app()->getLocale()).'"><meta charset="utf-8"><title>'.e(__('mail_previews.failed')).'</title><p>'.e(__('mail_previews.failed')).'</p></html>',
                    'text' => __('mail_previews.failed'),
                ];
            }
        });
    }

    public function render(MailMessage|Mailable $mail): array
    {
        $html = (string) $mail->render();
        $data = $mail instanceof MailMessage ? $mail->data() : $mail->buildViewData();
        if ($mail->markdown) {
            $text = (string) app(Markdown::class)->renderText($mail->markdown, $data);
        } elseif ($mail instanceof Mailable && $mail->textView) {
            $text = view($mail->textView, $data)->render();
        } elseif ($mail instanceof MailMessage && is_array($mail->view) && isset($mail->view['text'])) {
            $text = view($mail->view['text'], $data)->render();
        } else {
            $text = html_entity_decode(strip_tags(preg_replace('/<\/(p|div|tr|h[1-6])>|<br\s*\/?>/i', "\n", $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return ['subject' => $this->subject($mail), 'html' => $html, 'text' => $text];
    }

    public function response(array $rendered, string $format = 'html'): Response
    {
        return response($rendered[$format], 200, [
            'Content-Type' => $format === 'text' ? 'text/plain; charset=UTF-8' : 'text/html; charset=UTF-8',
            'Content-Security-Policy' => self::CSP,
            'X-Robots-Tag' => 'noindex',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
