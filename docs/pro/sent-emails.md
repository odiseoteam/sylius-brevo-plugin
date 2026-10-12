# Sent emails <span class="pro-badge">Pro</span>

<ProNotice />

Every email sent through Brevo is logged: email, recipient, template, language, order and customer,
and its status, kept up to date by the transactional [webhook](./webhooks).

![Brevo emails sent, with their delivery status](/images/pro-sent-emails.png){.screenshot}

## Statuses

Sent, delivered, opened, clicked, deferred, soft or hard bounce, spam, blocked, invalid address,
unsubscribed. Events can arrive in any order: the status only moves forward.

## Where to see them

- **Brevo > Sent emails**: every email, filtered by channel, status, email, recipient and date.
- **Order and customer pages**: the emails sent for that order or to that customer.
- **Blocked addresses**: after a hard bounce or a block, the customer page warns that Brevo stopped
  delivering to the address, with an **Unblock in Brevo** button.

![A customer whose address Brevo blocked](/images/pro-customer-emails.png){.screenshot}

## Cleaning up

Remove old logs and webhook events from a cron:

```bash
bin/console odiseo:brevo:logs:purge --older-than=90
```
