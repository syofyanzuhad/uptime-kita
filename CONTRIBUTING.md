# Contributing to Uptime Kita

Thank you for your interest in contributing to Uptime Kita! We welcome contributions from the community and are grateful for any help you can provide.

## 🚀 Development Workflow

### Pull Request Process

We use GitHub Actions to ensure code quality and maintain consistency across the project. When you create a pull request, the following checks will automatically run:

#### Automated Checks

1. **Lint & Format Check** - Ensures code follows our style guidelines
   - PHP code style check with Laravel Pint
   - Frontend formatting check with Prettier
   - ESLint for JavaScript/TypeScript code quality

2. **TypeScript Type Check** - Validates TypeScript types
   - Runs `vue-tsc --noEmit` to check for type errors

3. **Build Check** - Verifies that the application builds successfully
   - Ensures all assets compile without errors

4. **Tests** - Runs the full test suite
   - Executes Pest tests with coverage
   - Uploads coverage reports to Codecov

#### Auto-Formatting

If formatting issues are detected, our auto-format workflow will:
- Automatically fix PHP code style with Pint
- Format frontend code with Prettier
- Fix ESLint issues
- Commit the changes back to your PR branch
- Leave a comment on the PR

### Local Development Setup

To run the same checks locally before submitting a PR:

```bash
# Option 1: Run all checks at once (recommended)
npm run check-pr

# Option 2: Run checks individually
# Install dependencies
composer install
npm install

# Run PHP code style check
vendor/bin/pint --test

# Run frontend formatting check
npm run format:check

# Run frontend linting
npm run lint

# Run TypeScript type check
npx vue-tsc --noEmit

# Run tests
./vendor/bin/pest

# Build assets
npm run build
```

### Fixing Issues Locally

If you encounter formatting issues:

```bash
# Fix PHP code style
vendor/bin/pint

# Fix frontend formatting
npm run format

# Fix frontend linting issues
npm run lint
```

## Commit Message Convention

We follow conventional commit messages:

- `feat(component): add new feature` - New features
- `fix(component): resolve bug` - Bug fixes
- `docs(component): update documentation` - Documentation changes
- `style(component): format code` - Code formatting
- `refactor(component): improve code structure` - Code refactoring
- `test(component): add tests` - Test additions
- `chore(component): maintenance tasks` - Maintenance work

## Getting Started

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run the checks locally (`npm run check-pr`)
5. Commit your changes using conventional commit messages
6. Push to your branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

## Questions?

If you have any questions or need help, please open an issue on [GitHub](https://github.com/syofyanzuhad/uptime-kita/issues).

Thank you for contributing to Uptime Kita!