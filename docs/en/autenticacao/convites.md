---
title: User invitation
parent: Authentication
grand_parent: English
nav_order: 1
---

# User invitation

Someone from outside becomes a user **by invitation, and only by invitation**. An admin
opens `/admin/convites` — or, with tenancy, whoever holds `admin_app` opens
`/app/{organization}/convites` — and picks e-mail, role and organization; the kit sends a
link carrying a single-use token.

**Whoever invites doesn't need to know whether the address already has an account.** The kit
decides at acceptance time, and both paths use the same invitation and the same link:

| The address | What happens on acceptance |
|---|---|
| has **no** account | the person sets their own password and is born with the right role, in the right context, and with the e-mail already verified — the token proves ownership of the address |
| **already has** an account | it is an **access offer**: nobody is signed up again. The person logs in with the password they already have, confirms, and is linked to the organization with the invitation's role — their access in other organizations stays untouched |

On the offer path the token is **not enough**: acceptance requires the authenticated account
to be the invited e-mail, checked in the model and not in the screen's query. An intercepted
link is not access without the password of the invited address.

And saying **no** is possible. The user menu gains **Received invitations** (Convites
recebidos), with the count of pending offers and the accept and decline actions; a decline
is **recorded**, the invitation stops being valid (including through the link), and whoever
administers sees "Declined" in the listing instead of re-inviting someone who already said
no. The e-mail link remains the canonical path: it also works for someone who doesn't belong
to any organization yet and therefore can't reach that screen.

The acceptance screen is Filament's native registration page (`/app/register`), with one
guard: **without a valid token in the query string it refuses and redirects to login**.
There is no open sign-up.

| What | How |
|---|---|
| Token | `Str::random(64)`, stored **hashed** (`sha256`) — a leaked database dump is not access |
| Lifetime | `KIT_CONVITE_VALIDADE_DIAS` (7 days by default) |
| In bulk | **Invite in bulk** in the listing header: paste the addresses, one role and one organization for the whole batch. Up to `KIT_CONVITE_LIMITE_LOTE` (100 by default) — one bad address **does not stop the others**, and the summary tells you how many went out and why the rest did not |
| Usage | **single use**: for a new account, `aceito_em` is stamped in the same transaction that creates the user; for an offer, by a conditional `update` — which is what keeps two clicks from counting twice |
| Reminder | `KIT_CONVITE_LEMBRETES_DIAS` (D+3 and D+5 by default, counted from the send): the kit sends **one** reminder per invitation per due day, carrying a **second, parallel link** — the original link **keeps working**, and nothing is revoked even if the reminder lands in spam. The cap is the number of days in the list, and an empty list turns the feature off. Every day must be **smaller** than the lifetime, otherwise the invitation expires before the reminder is due and no reminder ever goes out |
| Resend | issues a new token and **kills the previous links** — the one from the send and the one from the last reminder |
| Revoke | deletes the invitation; the link stops working immediately, and the deletion lands in `/infra/audits` |
| Edit | **does not exist** — the invitation was already sent; fix it by revoking and creating another |

> ⚠️ **Invitations depend on two environment facts.** `MAIL_MAILER` at its `log` default
> only writes the e-mail to `storage/logs` — nothing leaves the machine. And the
> notification is queueable with `QUEUE_CONNECTION=database`: **without a running worker
> the invitation never goes out**. `composer dev` starts one; on a deploy, use
> `php artisan queue:work`. A stalled queue shows up in the `/infra` monitor. **Multiply that
> by N for bulk invitations**: a batch of a hundred puts a hundred rows in `jobs` and delivers
> zero, while the screen says "a hundred sent" — because they were, to the queue. With
> `QUEUE_CONNECTION=sync` it is the opposite: each e-mail is an SMTP handshake inside the
> request, and a hundred of them hit `max_execution_time`. That is what the batch limit
> protects.

> ⚠️ **Reminders need both of the above AND the scheduler.** They are sent by
> `kit:convites-lembrar`, scheduled in `routes/console.php` for 08:00 — without
> `php artisan schedule:work` (or the docker compose `scheduler` service) it is never called.
> And the invitation's counter **goes up even with the worker stopped**: the write happens
> before the notification is queued, on purpose, so that a permanently broken address cannot
> make the cron retry the same invitation every day forever. The consequence is honest: a
> stopped worker spends reminders without delivering e-mail. On an installation with old
> pending invitations, rehearse with `MAIL_MAILER=log` — which is the kit's default.

The invitation's role decides the context of the assignment: a role of the `/app` panel is
granted inside the invitation's organization; a role of `/admin` or `/infra` is granted in
the global context — being an admin of one organization is not a credential to administer
the installation.

