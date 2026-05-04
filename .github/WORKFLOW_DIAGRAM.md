# Code Review Workflow Diagrams

Visual representations of the code review process and automation.

## 📊 Complete Pull Request Lifecycle

```mermaid
graph TB
    Start([Developer starts work]) --> Branch[Create feature branch]
    Branch --> Code[Write code & tests]
    Code --> Commit[Commit changes]
    Commit --> Push[Push to GitHub]
    Push --> PR[Open Pull Request]
    
    PR --> Template[PR template auto-fills]
    Template --> Checks{Automated Checks}
    
    Checks --> |Run in parallel| Test[PHPUnit Tests]
    Checks --> |Run in parallel| Style[Code Quality]
    Checks --> |Run in parallel| Security[Security Scan]
    Checks --> |Run in parallel| Validate[PR Validation]
    Checks --> |Run in parallel| Coverage[Test Coverage]
    Checks --> |Run in parallel| Perf[Performance Check]
    Checks --> |Run in parallel| DB[Database Review]
    Checks --> |Run in parallel| Label[Auto-labeling]
    
    Test --> ChecksPass{All checks pass?}
    Style --> ChecksPass
    Security --> ChecksPass
    Validate --> ChecksPass
    Coverage --> ChecksPass
    Perf --> ChecksPass
    DB --> ChecksPass
    Label --> ChecksPass
    
    ChecksPass --> |No| FixChecks[Fix issues]
    FixChecks --> Commit
    
    ChecksPass --> |Yes| Assign[CODEOWNERS assigns reviewers]
    Assign --> Review[Code Review]
    
    Review --> Feedback{Feedback?}
    Feedback --> |Changes requested| Address[Address feedback]
    Address --> Commit
    
    Feedback --> |Approved| Approvals{Enough approvals?}
    Approvals --> |No| WaitApproval[Wait for more approvals]
    WaitApproval --> Review
    
    Approvals --> |Yes| Conversations{All resolved?}
    Conversations --> |No| Resolve[Resolve conversations]
    Resolve --> Review
    
    Conversations --> |Yes| Merge[Merge PR]
    Merge --> Deploy{Deploy?}
    Deploy --> |Yes| DeployProd[Deploy to production]
    Deploy --> |No| Done([Done])
    DeployProd --> Done
    
    style Start fill:#90EE90
    style Done fill:#90EE90
    style Checks fill:#FFD700
    style ChecksPass fill:#FF6B6B
    style Approvals fill:#FF6B6B
    style Merge fill:#4169E1
```

## 🤖 Automated Checks Flow

```mermaid
graph LR
    PR[Pull Request Opened] --> Trigger[Trigger Workflows]
    
    Trigger --> Check1[PR Validation]
    Trigger --> Check2[Automated Review]
    Trigger --> Check3[Security Review]
    Trigger --> Check4[Database Review]
    Trigger --> Check5[Coverage Review]
    Trigger --> Check6[Performance Review]
    Trigger --> Check7[Auto-label]
    Trigger --> Check8[Review Reminder]
    
    Check1 --> V1[Check title format]
    Check1 --> V2[Check PR size]
    Check1 --> V3[Check conflicts]
    
    Check2 --> A1[Run Laravel Pint]
    Check2 --> A2[Check debug statements]
    Check2 --> A3[Check TODOs]
    Check2 --> A4[Check sensitive data]
    
    Check3 --> S1[Run Trivy scanner]
    Check3 --> S2[Check SQL injection]
    Check3 --> S3[Upload to Security tab]
    
    Check4 --> D1[Detect migrations]
    Check4 --> D2[Add checklist comment]
    Check4 --> D3[Add database label]
    
    Check5 --> C1[Run tests with coverage]
    Check5 --> C2[Upload to Codecov]
    Check5 --> C3[Comment on PR]
    
    Check6 --> P1[Check N+1 queries]
    Check6 --> P2[Check missing indexes]
    
    Check7 --> L1[Label by file type]
    Check7 --> L2[Label by size]
    
    Check8 --> R1[Check review age]
    Check8 --> R2[Send reminder if >24h]
    
    V1 --> Result[All Results]
    V2 --> Result
    V3 --> Result
    A1 --> Result
    A2 --> Result
    A3 --> Result
    A4 --> Result
    S1 --> Result
    S2 --> Result
    S3 --> Result
    D1 --> Result
    D2 --> Result
    D3 --> Result
    C1 --> Result
    C2 --> Result
    C3 --> Result
    P1 --> Result
    P2 --> Result
    L1 --> Result
    L2 --> Result
    R1 --> Result
    R2 --> Result
    
    Result --> Status{Pass?}
    Status --> |Yes| Success[✅ Ready for review]
    Status --> |No| Fail[❌ Needs fixes]
    
    style PR fill:#4169E1
    style Success fill:#90EE90
    style Fail fill:#FF6B6B
```

## 👥 CODEOWNERS Assignment Flow

```mermaid
graph TB
    PR[Pull Request Created] --> Analyze[Analyze changed files]
    
    Analyze --> Backend{Backend files?}
    Analyze --> Frontend{Frontend files?}
    Analyze --> Database{Database files?}
    Analyze --> DevOps{DevOps files?}
    Analyze --> Security{Security files?}
    
    Backend --> |Yes| AssignBackend[Assign @backend-team]
    Frontend --> |Yes| AssignFrontend[Assign @frontend-team]
    Database --> |Yes| AssignDatabase[Assign @database-team]
    DevOps --> |Yes| AssignDevOps[Assign @devops-team]
    Security --> |Yes| AssignSecurity[Assign @security-team]
    
    AssignBackend --> Notify[Notify reviewers]
    AssignFrontend --> Notify
    AssignDatabase --> Notify
    AssignDevOps --> Notify
    AssignSecurity --> Notify
    
    Notify --> Review[Reviewers notified]
    
    style PR fill:#4169E1
    style Review fill:#90EE90
```

## 🔄 Review Iteration Flow

```mermaid
sequenceDiagram
    participant Dev as Developer
    participant GH as GitHub
    participant CI as CI/CD
    participant Rev as Reviewer
    
    Dev->>GH: Push code
    GH->>CI: Trigger workflows
    CI->>CI: Run automated checks
    CI->>GH: Report results
    
    alt Checks fail
        GH->>Dev: ❌ Checks failed
        Dev->>Dev: Fix issues
        Dev->>GH: Push fixes
        GH->>CI: Re-run checks
    end
    
    CI->>GH: ✅ All checks pass
    GH->>Rev: Assign reviewers
    Rev->>Rev: Review code
    
    alt Changes requested
        Rev->>GH: Request changes
        GH->>Dev: Notify developer
        Dev->>Dev: Address feedback
        Dev->>GH: Push changes
        GH->>CI: Re-run checks
        CI->>GH: Report results
        GH->>Rev: Request re-review
    end
    
    Rev->>GH: Approve PR
    GH->>Dev: ✅ Approved
    Dev->>GH: Merge PR
    GH->>CI: Trigger deployment
```

## 🏷️ Auto-labeling Logic

```mermaid
graph TB
    PR[Pull Request] --> Files[Analyze changed files]
    
    Files --> Check1{app/Http/Controllers/?}
    Files --> Check2{resources/views/?}
    Files --> Check3{database/migrations/?}
    Files --> Check4{tests/?}
    Files --> Check5{.github/workflows/?}
    Files --> Check6{docker*?}
    Files --> Check7{*.md?}
    Files --> Check8{app/Models/?}
    Files --> Check9{app/Services/?}
    
    Check1 --> |Yes| L1[backend]
    Check2 --> |Yes| L2[frontend]
    Check3 --> |Yes| L3[database]
    Check4 --> |Yes| L4[tests]
    Check5 --> |Yes| L5[ci/cd]
    Check6 --> |Yes| L6[docker]
    Check7 --> |Yes| L7[documentation]
    Check8 --> |Yes| L8[models]
    Check9 --> |Yes| L9[services]
    
    Files --> Size[Calculate changes]
    Size --> S1{< 100 lines?}
    Size --> S2{100-300 lines?}
    Size --> S3{300-600 lines?}
    Size --> S4{600-1000 lines?}
    Size --> S5{> 1000 lines?}
    
    S1 --> |Yes| SL1[size/XS]
    S2 --> |Yes| SL2[size/S]
    S3 --> |Yes| SL3[size/M]
    S4 --> |Yes| SL4[size/L]
    S5 --> |Yes| SL5[size/XL]
    
    L1 --> Apply[Apply labels]
    L2 --> Apply
    L3 --> Apply
    L4 --> Apply
    L5 --> Apply
    L6 --> Apply
    L7 --> Apply
    L8 --> Apply
    L9 --> Apply
    SL1 --> Apply
    SL2 --> Apply
    SL3 --> Apply
    SL4 --> Apply
    SL5 --> Apply
    
    Apply --> Done[Labels applied]
    
    style PR fill:#4169E1
    style Done fill:#90EE90
```

## 🔒 Security Check Flow

```mermaid
graph TB
    PR[Pull Request] --> Security[Security Review Job]
    
    Security --> Trivy[Run Trivy Scanner]
    Security --> SQL[Check SQL Injection]
    Security --> Sensitive[Check Sensitive Data]
    
    Trivy --> T1[Scan filesystem]
    Trivy --> T2[Check dependencies]
    Trivy --> T3[Find vulnerabilities]
    
    SQL --> Q1[Check DB::raw with variables]
    SQL --> Q2[Check whereRaw with variables]
    SQL --> Q3[Check selectRaw with variables]
    
    Sensitive --> D1[Check for passwords]
    Sensitive --> D2[Check for API keys]
    Sensitive --> D3[Check for tokens]
    Sensitive --> D4[Check for secrets]
    
    T3 --> Results[Collect results]
    Q3 --> Results
    D4 --> Results
    
    Results --> Upload[Upload to GitHub Security]
    Upload --> Notify{Critical issues?}
    
    Notify --> |Yes| Alert[🚨 Alert team]
    Notify --> |No| Pass[✅ Pass]
    
    Alert --> Block[Block merge]
    Pass --> Allow[Allow merge]
    
    style PR fill:#4169E1
    style Alert fill:#FF6B6B
    style Pass fill:#90EE90
```

## 📊 Branch Protection Flow

```mermaid
graph TB
    Merge[Attempt to merge PR] --> Check1{Pull request?}
    
    Check1 --> |No| Block1[❌ Blocked: Direct push not allowed]
    Check1 --> |Yes| Check2{Approvals?}
    
    Check2 --> |No| Block2[❌ Blocked: Need approvals]
    Check2 --> |Yes| Check3{Status checks?}
    
    Check3 --> |Failed| Block3[❌ Blocked: Checks must pass]
    Check3 --> |Passed| Check4{Conversations resolved?}
    
    Check4 --> |No| Block4[❌ Blocked: Resolve conversations]
    Check4 --> |Yes| Check5{Branch up to date?}
    
    Check5 --> |No| Block5[❌ Blocked: Update branch]
    Check5 --> |Yes| Check6{Code owners approved?}
    
    Check6 --> |No| Block6[❌ Blocked: Need code owner approval]
    Check6 --> |Yes| Allow[✅ Merge allowed]
    
    Allow --> Strategy{Merge strategy?}
    Strategy --> Squash[Squash and merge]
    Strategy --> Rebase[Rebase and merge]
    Strategy --> MergeCommit[Create merge commit]
    
    Squash --> Success[✅ Merged]
    Rebase --> Success
    MergeCommit --> Success
    
    style Merge fill:#4169E1
    style Success fill:#90EE90
    style Block1 fill:#FF6B6B
    style Block2 fill:#FF6B6B
    style Block3 fill:#FF6B6B
    style Block4 fill:#FF6B6B
    style Block5 fill:#FF6B6B
    style Block6 fill:#FF6B6B
```

## 🚀 Deployment Flow (After Merge)

```mermaid
graph LR
    Merge[PR Merged] --> Branch{Which branch?}
    
    Branch --> |develop| Staging[Deploy to Staging]
    Branch --> |main| Production[Deploy to Production]
    Branch --> |release/*| Release[Deploy to Release]
    
    Staging --> TestStaging[Run smoke tests]
    TestStaging --> NotifyStaging[Notify team]
    
    Production --> Backup[Backup database]
    Backup --> Deploy[Zero-downtime deploy]
    Deploy --> Verify[Verify deployment]
    Verify --> Rollback{Success?}
    
    Rollback --> |No| RollbackAction[Rollback deployment]
    Rollback --> |Yes| Monitor[Monitor metrics]
    
    RollbackAction --> Alert[Alert team]
    Monitor --> NotifyProd[Notify team]
    
    Release --> TestRelease[Run full test suite]
    TestRelease --> NotifyRelease[Notify team]
    
    style Merge fill:#4169E1
    style Monitor fill:#90EE90
    style Alert fill:#FF6B6B
```

## 📈 Review Metrics Flow

```mermaid
graph TB
    PR[Pull Request] --> Track[Track metrics]
    
    Track --> M1[Time to first review]
    Track --> M2[Number of review rounds]
    Track --> M3[Time to merge]
    Track --> M4[Number of comments]
    Track --> M5[Test coverage change]
    Track --> M6[PR size]
    Track --> M7[Files changed]
    
    M1 --> Analyze[Analyze trends]
    M2 --> Analyze
    M3 --> Analyze
    M4 --> Analyze
    M5 --> Analyze
    M6 --> Analyze
    M7 --> Analyze
    
    Analyze --> Report[Generate reports]
    Report --> Improve[Identify improvements]
    
    Improve --> I1[Reduce PR size]
    Improve --> I2[Faster reviews]
    Improve --> I3[Better tests]
    Improve --> I4[Clearer descriptions]
    
    I1 --> Better[Better process]
    I2 --> Better
    I3 --> Better
    I4 --> Better
    
    style PR fill:#4169E1
    style Better fill:#90EE90
```

## 🎯 Quick Reference

### PR States

```
Draft → Open → In Review → Changes Requested → Re-review → Approved → Merged
  ↓       ↓         ↓              ↓              ↓          ↓         ↓
 WIP    Checks   Reviewing    Addressing     Re-checking  Ready   Deployed
```

### Check Status

```
⏳ Pending → 🔄 Running → ✅ Passed
                      ↓
                   ❌ Failed → 🔧 Fix → 🔄 Re-run
```

### Review Status

```
👀 Review Requested → 💬 Commented → ✅ Approved
                   ↓
                🔄 Changes Requested → 🔧 Addressed → 👀 Re-review
```

---

**Note**: These diagrams represent the ideal workflow. Actual implementation may vary based on your team's needs and configuration.

For more details, see:
- [CODE_REVIEW_SETUP.md](CODE_REVIEW_SETUP.md)
- [CODE_REVIEW_GUIDELINES.md](CODE_REVIEW_GUIDELINES.md)
- [BRANCH_PROTECTION_SETUP.md](BRANCH_PROTECTION_SETUP.md)
