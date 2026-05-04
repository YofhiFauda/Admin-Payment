# Code Review Quick Reference

Quick reference guide for common code review tasks and commands.

## 📋 Quick Links

- [Full Setup Guide](CODE_REVIEW_SETUP.md)
- [Review Guidelines](CODE_REVIEW_GUIDELINES.md)
- [Branch Protection Setup](BRANCH_PROTECTION_SETUP.md)
- [CODEOWNERS](CODEOWNERS)

## 🚀 Quick Start

### Creating a Pull Request

```bash
# 1. Create feature branch
git checkout develop
git pull origin develop
git checkout -b feature/my-feature

# 2. Make changes and commit
git add .
git commit -m "feat: add new feature"

# 3. Push to remote
git push origin feature/my-feature

# 4. Open PR on GitHub
# The PR template will auto-populate
```

### Reviewing a Pull Request

```bash
# 1. Checkout PR locally
gh pr checkout <PR-number>

# 2. Run tests
php artisan test

# 3. Check code style
./vendor/bin/pint --test

# 4. Review changes
git diff develop...HEAD

# 5. Leave review on GitHub
```

## 📝 PR Title Format

Use [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: add user authentication
fix: resolve login bug
docs: update API documentation
style: format code with Pint
refactor: extract service class
perf: optimize database queries
test: add unit tests for auth
build: update dependencies
ci: add code review workflow
chore: update .gitignore
```

## ✅ PR Checklist

Before requesting review:

- [ ] Self-reviewed code
- [ ] Tests added/updated
- [ ] All tests passing
- [ ] Code style checks passing
- [ ] No debug statements
- [ ] Documentation updated
- [ ] PR template filled out
- [ ] Linked related issues

## 🏷️ PR Labels

Automatically added based on changes:

| Label | Trigger |
|-------|---------|
| `backend` | Changes in `app/Http/Controllers/` |
| `frontend` | Changes in `resources/views/` or `resources/js/` |
| `database` | Changes in `database/migrations/` |
| `tests` | Changes in `tests/` |
| `ci/cd` | Changes in `.github/workflows/` |
| `docker` | Changes in Docker files |
| `documentation` | Changes in `.md` files |

Size labels:

| Label | Lines Changed |
|-------|---------------|
| `size/XS` | < 100 |
| `size/S` | 100-300 |
| `size/M` | 300-600 |
| `size/L` | 600-1000 |
| `size/XL` | > 1000 |

## 💬 Review Comment Prefixes

Use these prefixes for clarity:

```
🔴 BLOCKER: Must be fixed before merge
🟡 IMPORTANT: Should be fixed before merge
🟢 SUGGESTION: Nice to have, not required
💡 QUESTION: Asking for clarification
👍 PRAISE: Acknowledging good work
```

Example:
```
🔴 BLOCKER: This SQL query is vulnerable to injection.
Please use parameter binding instead.
```

## 🔍 Common Review Checks

### Security
```bash
# Check for sensitive data
git diff develop...HEAD | grep -iE "(password|secret|api_key|token)"

# Check for SQL injection risks
git diff develop...HEAD | grep -E "DB::raw.*\$|->whereRaw.*\$"
```

### Performance
```bash
# Check for N+1 queries
git diff develop...HEAD | grep -E "foreach.*->.*->"

# Check for missing indexes in migrations
git diff develop...HEAD -- "database/migrations/*.php" | grep "foreign("
```

### Code Quality
```bash
# Run Laravel Pint
./vendor/bin/pint --test

# Run tests
php artisan test

# Check for debug statements
git diff develop...HEAD | grep -E "(dd\(|dump\(|var_dump\()"
```

## 🎯 Approval Requirements

| Branch | Approvals | Code Owners | Status Checks |
|--------|-----------|-------------|---------------|
| `main` | 2 | Required | All |
| `develop` | 1 | Optional | All |
| `release/*` | 2 | Required | All |
| `hotfix/*` | 1 | Optional | Critical |

## 🔧 GitHub CLI Commands

```bash
# Install GitHub CLI
# Windows: winget install GitHub.cli
# Mac: brew install gh
# Linux: See https://cli.github.com/

# Authenticate
gh auth login

# Create PR
gh pr create --title "feat: add feature" --body "Description"

# List PRs
gh pr list

# Checkout PR
gh pr checkout <PR-number>

# View PR
gh pr view <PR-number>

# Review PR
gh pr review <PR-number> --approve
gh pr review <PR-number> --request-changes --body "Comments"
gh pr review <PR-number> --comment --body "Looks good!"

# Merge PR
gh pr merge <PR-number> --squash
gh pr merge <PR-number> --rebase

# Close PR
gh pr close <PR-number>
```

## 🚨 Emergency Hotfix Process

```bash
# 1. Create hotfix branch from main
git checkout main
git pull origin main
git checkout -b hotfix/critical-fix

# 2. Make fix
# ... make changes ...

# 3. Commit and push
git add .
git commit -m "fix: critical security patch"
git push origin hotfix/critical-fix

# 4. Create PR to main (requires 1 approval)
gh pr create --base main --title "fix: critical security patch"

# 5. After merge, also merge to develop
git checkout develop
git pull origin develop
git merge main
git push origin develop
```

## 📊 Status Checks

All PRs must pass these checks:

| Check | Description | Blocks Merge |
|-------|-------------|--------------|
| PHPUnit Tests | All tests must pass | ✅ Yes |
| Code Quality | Laravel Pint style check | ✅ Yes |
| Frontend Tests | JavaScript tests | ✅ Yes |
| Security Scan | Trivy vulnerability scan | ⚠️ Warning |
| PR Validation | Title, size, conflicts | ✅ Yes |
| Coverage | Test coverage report | ⚠️ Warning |

## 🔄 Merge Strategies

### Squash and Merge (Recommended)
- Combines all commits into one
- Keeps history clean
- Use for feature branches

```bash
gh pr merge <PR-number> --squash
```

### Rebase and Merge
- Maintains individual commits
- Linear history
- Use for well-organized commits

```bash
gh pr merge <PR-number> --rebase
```

### Merge Commit
- Creates merge commit
- Preserves branch history
- Use for release branches

```bash
gh pr merge <PR-number> --merge
```

## 🛠️ Troubleshooting

### PR Blocked by Status Checks

**Problem**: Can't merge despite approvals

**Solution**:
```bash
# Check which checks failed
gh pr checks <PR-number>

# View detailed logs
gh run view <run-id>

# Re-run failed checks
gh run rerun <run-id>
```

### Merge Conflicts

**Problem**: PR has merge conflicts

**Solution**:
```bash
# Update your branch
git checkout feature/my-feature
git fetch origin
git merge origin/develop

# Resolve conflicts
# ... edit files ...

git add .
git commit -m "chore: resolve merge conflicts"
git push origin feature/my-feature
```

### Stale Approvals

**Problem**: Approval dismissed after new commits

**Solution**:
- Request re-approval from reviewers
- Or make changes in a new commit to preserve approval

### Can't Push to Branch

**Problem**: Branch is protected

**Solution**:
- Never push directly to `main` or `develop`
- Always use pull requests
- For emergencies, contact DevOps team

## 📚 Resources

### Documentation
- [Full Setup Guide](CODE_REVIEW_SETUP.md)
- [Review Guidelines](CODE_REVIEW_GUIDELINES.md)
- [Branch Protection](BRANCH_PROTECTION_SETUP.md)

### External Links
- [GitHub CLI](https://cli.github.com/)
- [Conventional Commits](https://www.conventionalcommits.org/)
- [Laravel Best Practices](https://github.com/alexeymezenin/laravel-best-practices)

### Team Contacts
- **DevOps Team**: devops@example.com
- **Tech Lead**: techlead@example.com
- **Security Team**: security@example.com

## 💡 Tips

### For Authors
- Keep PRs small (<300 lines)
- Write descriptive commit messages
- Self-review before requesting review
- Respond to feedback within 24 hours
- Add tests for new features

### For Reviewers
- Review within 24 hours
- Be constructive and specific
- Test locally for complex changes
- Explain reasoning for changes
- Acknowledge good work

### For Everyone
- Use draft PRs for work in progress
- Link related issues in PR description
- Update documentation with code changes
- Ask questions if unclear
- Be respectful and professional

---

**Need Help?**
- Check [CODE_REVIEW_GUIDELINES.md](CODE_REVIEW_GUIDELINES.md)
- Ask in team chat
- Contact DevOps team

**Last Updated**: 2026-05-04
