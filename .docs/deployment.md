# Auto‑deployment

## How it works

1. **Git push to `main`** → Codeberg sends a POST webhook to `https://domain/webhook.php`.
2. The PHP script verifies the HMAC‑SHA256 signature (shared secret).
3. If valid, it runs `deploy.sh` with the specified user.

## Modifying the existing deployment

After modifying the deployment script, ensure the deploy-user can write to that directory:

```bash
sudo chown >>deploy-user<<:www-data /var/www/domain 
sudo chmod 755 /var/www/domain
```

## Testing the webhook

```bash
PAYLOAD='{"ref":"refs/heads/main"}'
SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "secret-here" | cut -d ' ' -f2)
curl -k -X POST https://domain/webhook.php \
  -H "Content-Type: application/json" \
  -H "X-Gitea-Signature: sha256=$SIGNATURE" \
  -d "$PAYLOAD"
```
Expected response: `Deploy triggered` and HTTP status `202`.

## Troubleshooting

- **403 Forbidden**  
  Signature mismatch. Check the secret and the header name. Codeberg uses `X-Gitea-Signature`, not `X-Hub-Signature-256`.

- **Permission denied on `/var/www/domain`**  
  Run `sudo chown >>deploy-user<<:www-data /var/www/domain && sudo chmod 755 /var/www/domain` and retry.

- **`sudo: no tty present and no askpass program specified`**  
  This means the `sudo` command is prompting for a password, which should not happen. Double‑check the sudoers file to ensure it allows `www-data` to run the deploy script without a password.

- **Changes to `deploy.sh`?**  
  Ensure you fix the permissions and ownership of the script if needed, and verify that the webhook is pointing to the correct script location.
  ```bash
    sudo chown >>deploy-user<<:www-data /path/to/deploy.sh
    sudo chmod 750 /path/to/deploy.sh
  ```