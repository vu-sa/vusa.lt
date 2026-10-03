---
paths:
  - 'tests/Feature/**'
---

# Feature

## Mail::fake() cannot see notification mailables — assert via the array transport
When a notification's toMail() returns a Mailable (e.g. MemberRegistrationNotification → InformChairAboutMemberRegistration), the notification channel calls $mailable->send($mailer), which hands MailFake the rendered view array — MailFake only records Mailable arguments, so Mail::assertSent() never fires. Assert the real envelope instead: app('mail.manager')->mailer('array')->getSymfonyTransport()->messages() holds the sent Symfony SentMessages (recipients via getOriginalMessage()->getTo(), subject via ->getSubject()). Precedent: tests/Feature/Forms/MemberRegistrationNotificationMailTest.php.

## Where a feature test lives
A test goes where the code it exercises lives, not where it was first needed. Admin controllers: `tests/Feature/Admin/{Area}/{Controller}Test.php`, Area following the admin workspace section (e.g. `Admin/Reservations/` for reservations, cart, resources, categories). Workspace overviews: `Admin/Dashboard/{Workspace}DashboardTest.php`. Admin API controllers: `tests/Feature/Api/Admin/{Controller}Test.php`. Cross-cutting services keep their own directory with one `describe('Controller@action')` per action (e.g. `Approvals/`). Keep the `tests/README.md` tree and any docs `tests:` frontmatter in step when moving a file.
