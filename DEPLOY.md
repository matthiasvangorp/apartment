# Deploy key setup

This repo is pushed to using a GitHub **deploy key** (ed25519) with write access,
rather than a personal SSH key. The private key lives at `~/.ssh/apartment_deploy`
on the development machine.

## Pushing from the current machine

The private key is already in place and SSH is configured. Just use git normally:

```bash
cd ~/Sites/matthiasvangorpkft/apartment
git push
```

The remote `origin` points at `git@github-apartment:matthiasvangorp/apartment.git`
(note: `github-apartment`, not `github.com`). That alias resolves via
`~/.ssh/config` to the deploy key so it can't clash with a personal
GitHub account on the same machine.

## Setting up on a new machine

1. **Copy the private key** to `~/.ssh/apartment_deploy` (from a secure backup —
   never commit it). Set permissions:
   ```bash
   chmod 600 ~/.ssh/apartment_deploy
   ```

2. **Add an SSH alias** in `~/.ssh/config`:
   ```
   Host github-apartment
       HostName github.com
       User git
       IdentityFile ~/.ssh/apartment_deploy
       IdentitiesOnly yes
   ```

3. **Clone using the alias**:
   ```bash
   git clone git@github-apartment:matthiasvangorp/apartment.git
   ```

4. **Verify auth** (should greet you by name):
   ```bash
   ssh -T git@github-apartment
   ```

## Rotating the key

If the private key is lost or exposed:

1. Generate a new one:
   ```bash
   ssh-keygen -t ed25519 -f ~/.ssh/apartment_deploy -C "apartment@<host>"
   ```
2. Add the new public key at
   https://github.com/matthiasvangorp/apartment/settings/keys
   (Deploy keys → **Add deploy key**, tick *Allow write access*).
3. Remove the old deploy key from the same page.
