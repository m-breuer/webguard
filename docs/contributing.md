# Contributing

Contributions are welcome. Please keep changes focused, tested, and easy to review.

## Workflow

1. Fork the repository.
2. Create a branch for your feature or bug fix.

   ```bash
   git checkout -b feature-or-bugfix-name
   ```

3. Make the change and add tests for the behavior.
4. Run the relevant test suite.

   ```bash
   php artisan test
   ```

5. Commit with a descriptive Conventional Commits message.
6. Push the branch to your fork.
7. Open a pull request against the original repository's `main` branch.

## Expectations

- Write tests for new behavior and regressions.
- Keep existing tests passing.
- Prefer small pull requests with clear scope.
- Document user-facing behavior changes in the relevant docs file.
