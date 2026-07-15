# Releases and Changelog

WebGuard publishes release notes automatically through GitHub Actions. A successful CI run for a push to `main` triggers the `Tag` workflow, which creates a release tag, generates notes from the commits since the previous tag, and publishes or updates the corresponding GitHub release.

The generator groups Conventional Commits into feature, fix, performance, refactoring, documentation, test, CI/build, maintenance, breaking-change, and other-change sections. It also includes merged pull request branches when the repository name is provided.

The workflow can be started manually to regenerate notes for one tag or backfill all existing tags. For local previews, run:

```bash
php .github/scripts/generate-changelog.php \
  --tag=v0.82.1 \
  --repository=marcel-breuer/webguard
```

Release notes are maintained on the [GitHub Releases page](https://github.com/marcel-breuer/webguard/releases); the repository does not keep a separate generated `CHANGELOG.md` file.
