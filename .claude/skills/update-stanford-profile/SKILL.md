---
name: update-stanford-profile
description: Pull the latest SU-SWS/stanford_profile 13.x into all four lagunita profiles (sul, press, summer, csp) with the composer pull-* scripts, resolve conflicts, port dropped info.yml changes, then run composer update and audit. Use when asked to update the profiles from stanford_profile or do the periodic upstream/dependency refresh.
---

# Update profiles from stanford_profile + composer dependencies

Background: `AGENTS.md` ("Pulling upstream `stanford_profile` changes") and `docs/architecture.md`.
Never touch or read `deploy/`.

## 1. Preflight

- `git status --short` must be clean. If not, stop and ask the user. The pull scripts run
  `rm -rf docroot/profiles/lagunita && git checkout docroot/profiles/lagunita`, so any uncommitted
  profile edits will be **destroyed**.
- Branch off an up-to-date `4.x`: `git checkout 4.x && git pull && git checkout -b update-stanford-profile-$(date +%Y%m%d)`.
- Record the upstream range for later. The last squash commit's message names it:
  `git log --oneline --grep="Squashed 'docroot/profiles/lagunita/sul_profile/'" -1`
  (`changes from <OLD>..<NEW>`).

## 2. Pull each profile, one at a time

Run these in order, finishing each before you start the next:

```
composer pull-sul
composer pull-press
composer pull-summer
composer pull-csp
```

After each one:

1. `error: remote stanford_profile already exists.` is harmless.
2. Check for conflicts: `git diff --name-only --diff-filter=U`.
   - `CHANGELOG.md` in `csp_profile` or `summer_profile`: keep ours
     (`git checkout --ours <path> && git add <path>`).
   - `composer.json` and `<profile>.info.yml` are already resolved as ours by the script.
   - Any other file: look at both sides and merge by hand. Keep the site-specific behavior and take
     the upstream fixes. If the intent is unclear, ask the user.
3. If the merge stopped (the output shows conflicts instead of `Merge made by...`), commit it
   with `git commit --no-edit` once the conflicts are resolved.
4. `git status --short` must be clean before you run the next pull.

## 3. Port upstream info.yml / composer.json changes

The scripts keep ours for these files, so upstream changes to them are silently dropped. Get the
new range from the latest squash commit, then run:

```
git diff <OLD> <NEW> -- stanford_profile.info.yml composer.json
```

If anything changed (new `install:` modules, themes, or dependencies), copy it by hand into each
profile's `<profile>.info.yml` / `composer.json` and commit it.

## 4. Composer update

1. Check that no Stanford source checkout has uncommitted edits, because `composer update` can
   overwrite them:
   ```
   for d in docroot/modules/custom/* docroot/profiles/custom/stanford_profile; do
     [ -d "$d/.git" ] && [ -n "$(git -C "$d" status --porcelain)" ] && echo "DIRTY $d"; done
   ```
   If any are dirty, stop and ask the user.
2. Run `composer update --no-interaction` (it takes several minutes; use a long timeout). Confirm
   that every core patch applied and that the scaffold `htaccess.patch` and `robotstxt.patch` ran.
3. Run `composer audit`. For each advisory, find out what is holding it back with
   `composer why-not <pkg> <fixed-version>`, and with
   `composer update "<pkg>:<constraint>" -W --dry-run`. Fix it if the fix is safe. Otherwise
   report what blocks it. Don't downgrade modules to clear an advisory without asking.
   Advisories known to be blocked as of 2026-11-12:
   - `enshrined/svg-sanitize`: pinned to `~0.22` by `drupal/imagecache_external`.
   - `firebase/php-jwt` < 7: capped by `sainsburys/guzzle-oauth2-plugin`, which
     `stanford_profile` requires.
4. Commit: `git add composer.lock && git commit -m "Update composer dependencies"`.
   If `composer.json` changed, commit it too.

## 5. Report

Summarize for the user:

- the upstream commits pulled (`git log --oneline <OLD>..<NEW>` in the `stanford_profile` remote)
- the conflicts you resolved and how
- any info.yml or composer.json changes you ported
- the notable package bumps (core, drush, Stanford packages)
- the advisories that are still open, and what blocks each one

Don't push or open a PR unless the user asks. Suggest running the PHPUnit suite and `drush updb`
on at least one site before the PR, plus a `CHANGELOG.md` entry in the release PR.
