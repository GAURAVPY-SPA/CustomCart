# Sitepanel admin passwords

How Sitepanel administrator passwords are stored and checked, and how to get
back in when a password is forgotten.

A password hash can't be turned back into the password. There is no way to
look up a forgotten password, so you **reset** it instead.

## How passwords are stored

| | |
|---|---|
| Table | `tbl_admin` (one row per administrator; `admin_type` 1 = super admin, 2+ = sub-admin) |
| Account | `admin_username` (sign-in name) or `admin_email` |
| Password | `password_hash`: the output of PHP's `password_hash($password, PASSWORD_DEFAULT)` (bcrypt, starts with `$2y$`) |
| Legacy column | `admin_password`: plain text from older versions. Should be `NULL`. |
| Lockout | `failed_attempts`, `locked_until`: after 5 wrong passwords, sign-in is refused for 15 minutes |

The Sitepanel checks passwords with `password_verify()`
(`Modules\Sitepanel\Libraries\AdminAuth`). Every place that saves a password
stores a hash and clears `admin_password`:

- Sign-in: renews the hash when PHP's default algorithm changes.
- Sitepanel > Change Password.
- Sitepanel > Manage Subadmins (add, or edit with a new password).
- `php spark sitepanel:create-admin` (the first administrator).
- `php spark admin:reset-password` (see below).

Passwords and hashes are never written to the logs.

### Accounts with a legacy plain-text password

An account whose `admin_password` still holds a plain-text password signs in
with that password as before. On that successful sign-in, the password is saved
as a hash and `admin_password` is set to `NULL`. Nothing is reset: the
administrator keeps the same password, and only the hash is checked from then on.

To see which accounts haven't been converted yet (this doesn't show the passwords):

```sql
SELECT admin_id, admin_username FROM tbl_admin WHERE admin_password IS NOT NULL AND admin_password <> '';
```

## Change your password (signed in)

Use **Sitepanel > Change Password** (`/sitepanel/change_password`). Enter your
current password, then the new password twice. It must meet the
[password rules](#password-rules).

## Password rules

Every new administrator password has to meet the same rules, set in the admin password section
of the Sitepanel configuration, `modules/sitepanel/Config/Sitepanel.php`.

| Setting | Default | Meaning |
|---|---|---|
| `passwordMinLength` | `8` | Fewest characters |
| `passwordMaxLength` | `255` | Most characters |
| `passwordRequireLetter` | `true` | At least one letter A-Z or a-z |
| `passwordRequireNumber` | `true` | At least one number 0-9 |
| `passwordRequireSpecialCharacter` | `true` | At least one character that isn't a letter, number or space, e.g. `@ # $ ! % &` |

The rules apply to Change Password, Manage Subadmins (add, or a new password on
edit), `php spark sitepanel:create-admin` and `php spark admin:reset-password`.
Each place shows the current rules, and the checks live in
`Modules\Sitepanel\Libraries\AdminPasswordPolicy`.

To change a rule, edit the file, or override it in `.env`:

```
sitepanel.passwordMinLength = 10
sitepanel.passwordRequireSpecialCharacter = false
```

Signing in isn't affected. An existing password keeps working even if it
doesn't meet rules that were made stricter later; the new rules apply the
next time that password is changed.

## Forgotten password, option A: command line (recommended)

Run this in the project folder (`D:\wamp64\www\customcart`), on the server
or a machine connected to the same database:

```
php spark admin:reset-password admin
```

- The argument is the administrator's **username or email**. Leave it out to be asked for it.
- The command shows the account (name, username, email, id, type) and asks you to confirm.
- The command lists the [password rules](#password-rules), then you type the new
  password and confirm it. It isn't shown on screen, and it isn't a command
  argument, so it doesn't end up in shell history.
- If the password breaks a rule, the command says which one, and you can try again
  (3 tries). Nothing is saved until a password meets every rule and is confirmed.
- The command saves the new password as a hash, clears any plain-text password, and
  lifts a sign-in lockout. Nothing else on the account changes.
- The new password and its hash are never printed or logged.
- An inactive account keeps its status. Activate it in Manage Subadmins.

Only someone with shell access to the project can run it; it has no web page.

## Forgotten password, option B: directly in the database

Use this when you can't run `spark`, but can reach PHP and the database (for
example phpMyAdmin).

### 1. Generate a hash

Run this in any terminal (Command Prompt, PowerShell, Git Bash, Linux shell).
It waits for input: **type the new password and press Enter**. The password
appears on screen while you type it, but it isn't saved in shell history.

```
php -r "echo password_hash(rtrim(fgets(STDIN), chr(13).chr(10)), PASSWORD_DEFAULT), PHP_EOL;"
```

It prints a 60-character hash such as `$2y$12$…`, which you copy for step 2.
A new hash is different every time, even for the same password; that's normal.

Type the password; don't pipe it in. In Windows PowerShell,
`'secret' | php -r ...` adds an invisible byte-order mark to the input, so the
hash won't match the password you type at sign-in.

### 2. Save it on the account

In phpMyAdmin, open the `customcart` database, go to the **SQL** tab, and run
the statement below. Paste your hash and use the right username:

```sql
UPDATE tbl_admin
SET password_hash   = 'PASTE-THE-HASH-HERE',
    admin_password  = NULL,
    failed_attempts = 0,
    locked_until    = NULL,
    updated_at      = NOW()
WHERE admin_username = 'admin'
LIMIT 1;
```

- `admin_password = NULL` matters: a non-empty legacy plain-text password is
  checked instead of the hash.
- The hash contains `$` characters. That's fine inside SQL quotes, but if you run
  the statement from a shell (`mysql -e "..."`), the shell may replace them. Use
  the phpMyAdmin SQL tab or the `mysql>` prompt instead.
- Expect "1 row affected". "0 rows" means the username is wrong; check it with
  `SELECT admin_id, admin_username, admin_email FROM tbl_admin;`.

### 3. Sign in

Go to `/sitepanel/login` and sign in with the username and the new password.
If it is refused, check that the whole 60-character hash was pasted, with no
spaces, and that `admin_password` is `NULL`.
