# GitHub Code Review Setup - Complete Guide

This document provides a complete overview of the GitHub code review system implemented for this project.

## 📋 Table of Contents
- [Overview](#overview)
- [What's Included](#whats-included)
- [Quick Start](#quick-start)
- [Configuration Steps](#configuration-steps)
- [Workflow Overview](#workflow-overview)
- [Team Setup](#team-setup)
- [Customization](#customization)

## Overview

This repository includes a comprehensive GitHub code review system with:
- ✅ Automated PR validation and checks
- ✅ Standardized PR and issue templates
- ✅ Automatic reviewer assignment (CODEOWNERS)
- ✅ Security and quality scanning
- ✅ Detailed review guidelines
- ✅ Branch protection recommendations

## What's Included

### 1. Pull Request Template
**File**: `.github/pull_request_template.md`

Standardized PR description template that includes:
- Change type classification
- Testing checklist
- Security considerations
- Performance impact assessment
- Documentation requirements
- Deployment notes

### 2. Code Review Workflow
**File**: `.github/workflows/code-review.yml`

Automated workflow that runs on every PR:
- **PR Validation**: Checks PR title format, size, and merge conflicts
- **Automated Review**: Runs Laravel Pint, checks for debug statements, TODOs, and sensitive data
- **Security Review**: Runs Trivy scanner and checks for SQL injection risks
- **Database Review**: Detects migration changes and adds review checklist
- **Test Coverage**: Runs tests and reports coverage
- **Performance Review**: Checks for N+1 queries and missing indexes
- **Auto-labeling**: Automatically labels PRs based on changed files
- **Review Reminders**: Reminds about PRs waiting for review

### 3. CODEOWNERS
**File**: `.github/CODEOWNERS`

Automatic reviewer assignment based on file paths:
- Backend code → Backend team
- Frontend code → Frontend team
- Database changes → Database team
- Infrastructure → DevOps team
- Security-sensitive files → Security team
- Critical files → Multiple teams

### 4. Issue Templates
**Files**: `.github/ISSUE_TEMPLATE/`

Structured templates for:
- **Bug Reports**: Detailed bug reporting with severity levels
- **Feature Requests**: Structured feature proposals with use cases
- **Configuration**: Links to discussions and documentation

### 5. Review Guidelines
**File**: `.github/CODE_REVIEW_GUIDELINES.md`

Comprehensive guide covering:
- Review process and checklist
- What to look for in reviews
- Best practices for authors and reviewers
- Common issues and solutions
- Review etiquette
- Laravel-specific patterns

### 6. Branch Protection Setup
**File**: `.github/BRANCH_PROTECTION_SETUP.md`

Step-by-step guide for configuring:
- Branch protection rules
- Required status checks
- Approval requirements
- Branch strategy and naming conventions

## Quick Start

### For Repository Admins

1. **Update CODEOWNERS**
   ```bash
   # Edit .github/CODEOWNERS
   # Replace @your-org/team-name with actual GitHub teams or usernames
   ```

2. **Configure Branch Protection**
   - Follow `.github/BRANCH_PROTECTION_SETUP.md`
   - Set up protection for `main` and `develop` branches

3. **Set Up GitHub Teams** (if using organizations)
   - Create teams: backend-team, frontend-team, devops-team, etc.
   - Add team members
   - Grant repository access

4. **Configure Secrets** (if needed)
   - `CODECOV_TOKEN`: For code coverage reporting
   - `SLACK_WEBHOOK_URL`: For notifications (optional)

5. **Update Issue Template Links**
   ```bash
   # Edit .github/ISSUE_TEMPLATE/config.yml
   # Update URLs to match your repository
   ```

### For Developers

1. **Read the Guidelines**
   - Review `.github/CODE_REVIEW_GUIDELINES.md`
   - Understand the PR template requirements

2. **Create Feature Branch**
   ```bash
   git checkout develop
   git pull origin develop
   git checkout -b feature/your-feature-name
   ```

3. **Make Changes and Commit**
   ```bash
   git add .
   git commit -m "feat: add user authentication"
   git push origin feature/your-feature-name
   ```

4. **Open Pull Request**
   - Use the PR template (auto-populated)
   - Fill out all sections
   - Request reviewers (or let CODEOWNERS auto-assign)

5. **Address Review Feedback**
   - Respond to comments
   - Make requested changes
   - Mark conversations as resolved
   - Request re-review

## Configuration Steps

### Step 1: Update CODEOWNERS

Edit `.github/CODEOWNERS` and replace placeholder teams:

```diff
- * @your-org/core-team
+ * @acme-corp/core-team

- /app/Http/Controllers/ @your-org/backend-team
+ /app/Http/Controllers/ @acme-corp/backend-team @john-doe
```

Or use individual usernames:
```
/app/Http/Controllers/ @john-doe @jane-smith
/resources/views/ @frontend-dev
```

### Step 2: Configure Branch Protection

1. Go to **Settings → Branches**
2. Click **Add branch protection rule**
3. Follow the guide in `.github/BRANCH_PROTECTION_SETUP.md`

**Minimum recommended settings for `main`:**
- ✅ Require pull request reviews (2 approvals)
- ✅ Require status checks to pass
- ✅ Require conversation resolution
- ✅ Require linear history
- ❌ Allow force pushes
- ❌ Allow deletions

### Step 3: Set Up Required Status Checks

In branch protection settings, add these required checks:
- `PHPUnit Tests`
- `Code Quality`
- `Frontend Tests`
- `Security Review`
- `PR Validation`

These come from the workflows in `.github/workflows/`.

### Step 4: Configure Notifications (Optional)

For Slack notifications, add webhook URL:

1. Create Slack webhook: https://api.slack.com/messaging/webhooks
2. Add to GitHub Secrets: `SLACK_WEBHOOK_URL`
3. Notifications will be sent for security scans

### Step 5: Set Up Code Coverage (Optional)

For Codecov integration:

1. Sign up at https://codecov.io
2. Add repository
3. Get token and add to GitHub Secrets: `CODECOV_TOKEN`
4. Coverage reports will appear on PRs

## Workflow Overview

### Pull Request Lifecycle

```
1. Developer creates feature branch
   ↓
2. Developer opens PR (template auto-fills)
   ↓
3. Automated checks run:
   - Tests
   - Code quality
   - Security scan
   - PR validation
   ↓
4. CODEOWNERS auto-assigns reviewers
   ↓
5. Reviewers review code
   ↓
6. Developer addresses feedback
   ↓
7. Reviewers approve
   ↓
8. All checks pass
   ↓
9. PR merged (squash or rebase)
   ↓
10. Feature branch deleted
```

### Automated Checks

Every PR triggers these checks:

| Check | Purpose | Blocks Merge |
|-------|---------|--------------|
| PHPUnit Tests | Run all tests | ✅ Yes |
| Code Quality | Laravel Pint style check | ✅ Yes |
| Frontend Tests | Run JS tests | ✅ Yes |
| Security Scan | Trivy vulnerability scan | ⚠️ Warning |
| PR Validation | Check title, size, conflicts | ✅ Yes |
| Coverage Review | Test coverage report | ⚠️ Warning |
| Database Review | Migration checklist | ℹ️ Info |
| Performance Review | N+1 query detection | ⚠️ Warning |

### Review Requirements

| Branch | Approvals | Code Owners | Status Checks |
|--------|-----------|-------------|---------------|
| `main` | 2 | Required | All required |
| `develop` | 1 | Optional | All required |
| `release/*` | 2 | Required | All required |
| `hotfix/*` | 1 | Optional | Critical only |

## Team Setup

### GitHub Teams (Organizations)

Create these teams in your GitHub organization:

1. **@your-org/core-team** - Core maintainers
2. **@your-org/backend-team** - Backend developers
3. **@your-org/frontend-team** - Frontend developers
4. **@your-org/devops-team** - DevOps engineers
5. **@your-org/database-team** - Database specialists
6. **@your-org/security-team** - Security reviewers
7. **@your-org/qa-team** - QA engineers

### Individual Contributors

If not using organizations, use individual usernames:

```
# .github/CODEOWNERS
* @tech-lead

/app/Http/Controllers/ @backend-dev-1 @backend-dev-2
/resources/views/ @frontend-dev
/database/ @database-admin
/.github/workflows/ @devops-engineer
```

## Customization

### Adjust PR Size Thresholds

Edit `.github/workflows/code-review.yml`:

```yaml
if (changes < 100) {
  label = 'size/XS';
} else if (changes < 300) {  # Change these numbers
  label = 'size/S';
}
```

### Modify Required Approvals

Edit branch protection rules:
- Small teams: 1 approval
- Large teams: 2+ approvals
- Critical branches: 2+ approvals

### Add Custom Checks

Add new jobs to `.github/workflows/code-review.yml`:

```yaml
custom-check:
  name: Custom Check
  runs-on: ubuntu-latest
  steps:
    - name: Checkout code
      uses: actions/checkout@v4
    
    - name: Run custom check
      run: ./scripts/custom-check.sh
```

### Customize PR Template

Edit `.github/pull_request_template.md`:
- Add/remove sections
- Adjust checklists
- Add project-specific requirements

### Add More Issue Templates

Create new templates in `.github/ISSUE_TEMPLATE/`:

```yaml
# .github/ISSUE_TEMPLATE/performance_issue.yml
name: ⚡ Performance Issue
description: Report a performance problem
# ... rest of template
```

## Maintenance

### Regular Tasks

**Weekly:**
- Review open PRs
- Check for stale PRs (>7 days)
- Ensure reviewers are responsive

**Monthly:**
- Review and update CODEOWNERS
- Check branch protection rules
- Update required status checks
- Review automation effectiveness

**Quarterly:**
- Update review guidelines
- Review and improve templates
- Gather team feedback
- Adjust approval requirements

### Monitoring

Track these metrics:
- Average PR review time
- Number of PRs requiring changes
- Test coverage trends
- Security scan findings
- PR size distribution

### Troubleshooting

**PRs not getting reviewed?**
- Check CODEOWNERS assignments
- Verify team members have access
- Send review reminders
- Adjust notification settings

**Too many failed checks?**
- Review check requirements
- Provide better documentation
- Offer training sessions
- Adjust thresholds

**Reviews taking too long?**
- Reduce PR size
- Improve PR descriptions
- Provide better context
- Pair programming for complex changes

## Best Practices

### For Authors
1. Keep PRs small (<300 lines)
2. Write clear descriptions
3. Self-review before requesting review
4. Respond to feedback promptly
5. Add tests for new features

### For Reviewers
1. Review within 24 hours
2. Be constructive and specific
3. Test locally for complex changes
4. Explain reasoning for changes
5. Acknowledge good work

### For Teams
1. Establish review rotation
2. Set response time expectations
3. Hold regular review training
4. Share review best practices
5. Celebrate quality improvements

## Resources

### Documentation
- [CODE_REVIEW_GUIDELINES.md](.github/CODE_REVIEW_GUIDELINES.md) - Detailed review guide
- [BRANCH_PROTECTION_SETUP.md](.github/BRANCH_PROTECTION_SETUP.md) - Branch protection setup
- [pull_request_template.md](.github/pull_request_template.md) - PR template

### External Resources
- [GitHub Code Review Guide](https://github.com/features/code-review)
- [Laravel Best Practices](https://github.com/alexeymezenin/laravel-best-practices)
- [Conventional Commits](https://www.conventionalcommits.org/)
- [Semantic Versioning](https://semver.org/)

## Support

### Getting Help
1. Check documentation in `.github/`
2. Ask in team chat
3. Open a discussion on GitHub
4. Contact DevOps team

### Reporting Issues
If you find issues with the review process:
1. Open an issue using the bug report template
2. Tag with `process` label
3. Suggest improvements

## Next Steps

1. ✅ Review all documentation
2. ✅ Update CODEOWNERS with your teams
3. ✅ Configure branch protection
4. ✅ Set up required status checks
5. ✅ Train team on new process
6. ✅ Create first PR using new template
7. ✅ Gather feedback and iterate

---

**Version**: 1.0.0  
**Last Updated**: 2026-05-04  
**Maintained By**: DevOps Team

For questions or suggestions, open an issue or contact the team! 🚀
