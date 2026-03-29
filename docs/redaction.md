# Redaction

Redaction is **enabled by default**. Every packed file is scanned and detected secrets are replaced with `***REDACTED***`.

To disable redaction and include raw file contents:

```shell
proj2file run --no-redact
```

## What is detected

| Category | Examples |
|---|---|
| Private keys | PEM-encoded RSA, EC, DSA, OpenSSH keys |
| Env-style secrets | `DB_PASSWORD=...`, `API_KEY=...`, `AUTH_TOKEN=...` (value is masked, key name is kept) |
| AWS access keys | `AKIA...` patterns |
| GitHub tokens | `ghp_...`, `ghs_...` |
| GitLab tokens | `glpat-...` |
| OpenAI keys | `sk-...` |
| Slack tokens | `xoxb-...`, `xoxp-...` |
| Bearer tokens | `Bearer eyJ...` and similar |
| URL credentials | `scheme://user:password@host` (credentials masked) |
| Connection strings | `password=...` / `pwd=...` parameters |
| PHP array credentials | `$DB['PASSWORD'] = '...'` and similar PHP config assignments |
| Email addresses | `user@example.com` |
| IPv4 addresses | Non-loopback, non-broadcast IPs |
| JWT tokens | `eyJ...` three-segment tokens |
| Hex secrets | 32+ character hex strings in value positions |

The total number of redactions is printed after packing:

```
Redactions applied: 42
```
