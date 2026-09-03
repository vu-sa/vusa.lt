---
paths:
  - 'tests/Feature/**'
---

# Feature

## Mail::fake() cannot see notification mailables — assert via the array transport
When a notification's toMail() returns a Mailable (e.g. MemberRegistrationNotification → InformChairAboutMemberRegistration), the notification channel calls $mailable->send($mailer), which hands MailFake the rendered view array — MailFake only records Mailable arguments, so Mail::assertSent() never fires. Assert the real envelope instead: app('mail.manager')->mailer('array')->getSymfonyTransport()->messages() holds the sent Symfony SentMessages (recipients via getOriginalMessage()->getTo(), subject via ->getSubject()). Precedent: tests/Feature/Forms/MemberRegistrationNotificationMailTest.php.
