# AGENTS.md

Media processing extension for TYPO3 (PHP 8.1+, TYPO3 12.4 – 14.x).

## Code

- Keep `composer.json` constraints and the code compatible with every supported TYPO3 and PHP version.
- Don't abbreviate names.
- Use code comments sparingly. Comment only what the code can't say, and keep the wording precise and concise.

## Checks

- Iterate with focused checks, e.g. `composer phpstan` or `composer ecs`.
- Run `composer check` before finishing. It covers style, dependencies, refactoring and static analysis.
- Auto-fix with `composer ecs-fix` and `composer rector-fix`.
- Run the tests with `./vendor/bin/phpunit -c Tests/UnitTests.xml` and `./vendor/bin/phpunit -c Tests/FunctionalTests.xml --bootstrap Tests/FunctionalTestsBootstrap.php`.
- Don't weaken a check to make it pass. Fix the cause. `phpstan-baseline.neon` only shrinks.

## Git

- Commit only when asked.
- Use Conventional Commits, e.g. `fix(imgproxy): keep the leading slash`.
- Don't add AI attribution such as co-author trailers or generated-by footers.
