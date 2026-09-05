---
title: Active, inactive and deleted users
parent: Authentication
grand_parent: English
nav_order: 5
---

# Active, inactive and deleted users

Every user account has **three states**, and they override access to any panel:

- **Active**: signs in with password or social login as usual.
- **Inactive**: the **password** or social login still recognises the account, but the user lands on a warning saying the account was **deactivated** and asking them to **contact the administrator** to **Reactivate**; no session is opened.
- **Deleted**: deletion is logical; anyone trying to sign in lands on a warning with the deletion date and, depending on the role, can **Restore** from the `/admin` user list or from the **Recycle bin** in `/infra`.

Users with the `Desativar:User` permission see the deactivate action in the `/admin` user list, and the `Reativar:User` permission sees the matching action. No one can deactivate their own account, and the system refuses to deactivate the last active `master_global`. The protection applies on **password** login, social login and link confirmation.

An unavailable account also **cannot be impersonated**: the *Impersonate* action does not appear on the row of anyone who is inactive, pending approval or deleted. It is the same rule as panel access — if the person cannot sign in on their own, nobody signs in as them.

