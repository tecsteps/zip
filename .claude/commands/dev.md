# Feature Development Command

Develop a feature based on the specification: **$ARGUMENTS**

## Prerequisites

Before starting, verify:
1. The spec file exists at the provided path
2. The spec file has a corresponding questions file (replace `-specification.md` with `-questions.md`)

## Phase 0: Setup

### 0.1 Extract Feature Name and Paths
Parse the spec file path to extract:
- `spec_path`: The full path provided (e.g., `specs/2025-01-15_user_dashboard-specification.md`)
- `feature_name`: Extract from filename (e.g., `user_dashboard`)
- `questions_path`: Derive from spec path (e.g., `specs/2025-01-15_user_dashboard-questions.md`)
- `branch_name`: `feature/{feature_name}` (e.g., `feature/user_dashboard`)
- `worktree_path`: `../zip-{feature_name}-worktree`

### 0.2 Read Specification and Questions
1. Read the specification file completely
2. Read the questions file to understand context and decisions made
3. Identify all implementation phases from Section 5 of the spec
4. Create a todo list tracking all phases

### 0.3 Create Git Work Tree
Execute the following steps:
```bash
# Navigate to parent directory and create worktree
cd .. && git -C zip worktree add -b {branch_name} {feature_name}-worktree
```

If the branch already exists, use:
```bash
cd .. && git -C zip worktree add {feature_name}-worktree {branch_name}
```

### 0.4 Change Working Directory
All subsequent work must happen in the worktree:
```bash
cd ../{feature_name}-worktree
```

---

## Phase 1: Implementation

For each phase in Section 5 of the specification, execute in order:

### 1.1 Backend Implementation (Laravel Clean Code Expert)

Use the Task tool with `subagent_type=laravel-clean-code-expert`:

```
Implement Phase {N} of the feature specification.

Specification file: {spec_path}
Questions file: {questions_path}
Working directory: {worktree_path}

Requirements for this phase:
{Copy the specific requirements from the spec for this phase}

Instructions:
1. Read the full specification and questions files first
2. Check existing code patterns in the project
3. Use `search-docs` for any Laravel-specific implementations
4. Implement all requirements for this phase
5. Follow all conventions from CLAUDE.md
6. Run `vendor/bin/pint --dirty` after implementation

Do NOT implement frontend/UI components - focus only on:
- Migrations
- Models
- Services/Actions
- Controllers
- Form Requests
- API Resources
- Route definitions
```

### 1.2 Frontend Implementation (Tailwind Frontend Architect)

Use the Task tool with `subagent_type=tailwind-frontend-architect`:

```
Implement the frontend/UI components for Phase {N} of the feature specification.

Specification file: {spec_path}
Questions file: {questions_path}
Working directory: {worktree_path}

UI Requirements for this phase:
{Copy the UI-specific requirements from the spec}

Instructions:
1. Read the full specification and questions files first
2. Check existing UI components and patterns in the project
3. Use Flux UI components where available
4. For Livewire/Volt components, follow existing conventions
5. Implement responsive layouts (mobile-first)
6. Support dark mode if the project uses it
7. Run `vendor/bin/pint --dirty` after implementation
```

### 1.3 OpenRouter Integration (Conditional)

**Only execute this step if the specification mentions AI, LLM, OpenRouter, or AI-powered features.**

Use the Task tool with `subagent_type=openrouter-specialist`:

```
Implement OpenRouter integration for Phase {N} of the feature specification.

Specification file: {spec_path}
Questions file: {questions_path}
Working directory: {worktree_path}

AI/LLM Requirements for this phase:
{Copy the AI/LLM-specific requirements from the spec}

Instructions:
1. Read the full specification and questions files first
2. Check existing OpenRouter integration patterns in the project (if any)
3. Consult OpenRouter documentation for:
   - Optimal model selection for the use case
   - API integration best practices
   - Rate limiting and error handling
   - Cost optimization strategies
4. Implement the AI/LLM integration following Laravel conventions
5. Create appropriate service classes for OpenRouter API calls
6. Implement proper error handling and fallbacks
7. Add configuration options to config files (not hardcoded)
8. Run `vendor/bin/pint --dirty` after implementation

Focus on:
- Service classes for OpenRouter API communication
- Model selection logic (if multiple models needed)
- Prompt engineering and template management
- Response parsing and validation
- Rate limiting and retry logic
- Cost tracking (if specified)
```

### 1.4 Test Writing (Pest Test Writer)

Use the Task tool with `subagent_type=pest-test-writer`:

```
Write comprehensive tests for Phase {N} of the feature implementation.

Specification file: {spec_path}
Working directory: {worktree_path}

Testing Requirements from spec:
{Copy the testing requirements from the spec}

Instructions:
1. Read the specification to understand expected behaviors
2. Check existing test patterns in tests/Feature and tests/Unit
3. Write Pest tests for all functionality implemented in this phase
4. Cover:
   - Happy paths
   - Failure paths
   - Edge cases
   - Validation rules (use datasets where appropriate)
   - Authorization checks
5. Run the tests to ensure they pass: `php artisan test tests/Feature/{relevant_tests}`
6. For Livewire/Volt components, use appropriate test helpers
```

### 1.5 Phase Verification

After each phase:

1. Run all tests for the phase:
```bash
php artisan test --filter="{feature_related_tests}"
```

2. Run Pint:
```bash
vendor/bin/pint --dirty
```

3. Commit the phase:
```bash
git add -A && git commit -m "$(cat <<'EOF'
feat({feature_name}): implement phase {N} - {phase_description}

- {Summary of what was implemented}

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude Opus 4.5 <noreply@anthropic.com>
EOF
)"
```

---

## Phase 2: Quality Assurance

After ALL implementation phases are complete:

### 2.1 Architecture Guardian Review

Use the Task tool with `subagent_type=architecture-guardian`:

```
Perform a comprehensive architecture and code quality review for the feature implementation.

Specification file: {spec_path}
Working directory: {worktree_path}

Files to review:
{List all files created/modified during implementation}

Instructions:
1. Read all implementation files
2. Use JetBrains MCP `get_file_problems` on EVERY file to check for IDE errors
3. Evaluate against:
   - SOLID principles
   - Clean code standards
   - Laravel conventions
   - Livewire best practices (if applicable)
4. Check that all database queries are in service classes (not in Livewire components)
5. Produce the Architecture Guardian Report with actionable checklist
6. Flag any critical or major issues that must be fixed
```

If the Architecture Guardian finds Critical or Major issues:
- Fix each issue in the remediation checklist
- Run tests again
- Commit fixes:
```bash
git add -A && git commit -m "$(cat <<'EOF'
refactor({feature_name}): address architecture review findings

- {List fixes made}

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude Opus 4.5 <noreply@anthropic.com>
EOF
)"
```

### 2.2 Specification Compliance Verification

Use the Task tool with `subagent_type=spec-compliance-verifier`:

```
Verify that the implementation fully complies with the specification.

Specification file: {spec_path}
Questions file: {questions_path}
Working directory: {worktree_path}

Instructions:
1. Read the specification document completely
2. Extract ALL requirements from the spec (functional, non-functional, UI/UX, testing)
3. Trace each requirement to its implementation
4. Verify tests exist for each specified behavior
5. Check that all checkboxes in Section 5 of the spec can be marked as complete
6. Produce the Compliance Report with detailed findings
7. Flag any requirements that are:
   - Not implemented
   - Partially implemented
   - Deviated from specification
```

If compliance issues are found:
- Address each gap
- Run tests again
- Commit fixes:
```bash
git add -A && git commit -m "$(cat <<'EOF'
fix({feature_name}): address specification compliance gaps

- {List gaps addressed}

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude Opus 4.5 <noreply@anthropic.com>
EOF
)"
```

---

## Phase 3: Finalization

### 3.1 Final Test Run
```bash
php artisan test --parallel
```

### 3.2 Final Code Style Check
```bash
vendor/bin/pint --dirty
```

### 3.3 Push to Remote
```bash
git push -u origin {branch_name}
```

### 3.4 Create Pull Request

Use GitHub CLI to create the PR:

```bash
gh pr create --title "feat({feature_name}): {Feature title from spec}" --body "$(cat <<'EOF'
## Summary

{2-3 bullet points summarizing what this feature does, extracted from spec Section 1}

## Changes

{List of major changes organized by category:}
- **Database**: {migrations, schema changes}
- **Backend**: {models, services, controllers}
- **Frontend**: {Livewire components, views}
- **Tests**: {test files added}

## Specification Reference

- Specification: `{spec_path}`
- Questions: `{questions_path}`

## Test Plan

- [ ] All automated tests pass (`php artisan test --parallel`)
- [ ] Code style validated (`vendor/bin/pint --dirty`)
- [ ] Architecture review passed
- [ ] Specification compliance verified

## Screenshots

{If UI changes, note to add screenshots}

---

🤖 Generated with [Claude Code](https://claude.com/claude-code)
EOF
)"
```

### 3.5 Update Documentation

Update the `docs/` directory with any necessary documentation for the new feature:

1. Check if `docs/` directory exists and review its structure
2. Identify what documentation updates are needed:
   - New feature documentation (if user-facing)
   - API documentation (if new endpoints added)
   - Configuration documentation (if new config options)
   - Update existing docs that reference changed functionality

3. Create or update documentation files following existing conventions in `docs/`

4. Commit documentation updates:
```bash
git add docs/ && git commit -m "$(cat <<'EOF'
docs({feature_name}): add documentation for {feature_name}

- {List documentation changes}

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude Opus 4.5 <noreply@anthropic.com>
EOF
)"
```

5. Push the documentation commit:
```bash
git push
```

### 3.6 Report Completion

Inform the user:
- PR URL
- Summary of what was implemented
- Any notes or decisions made during implementation
- Documentation updates made
- Worktree location for manual testing: `../zip-{feature_name}-worktree`

---

## Error Handling

### If tests fail:
1. Analyze the failure
2. Fix the issue
3. Run tests again
4. Continue with the workflow

### If architecture review finds critical issues:
1. Address all critical issues first
2. Then address major issues
3. Re-run the architecture review to verify fixes
4. Continue with the workflow

### If spec compliance fails:
1. Implement missing requirements
2. Fix deviations
3. Re-run spec compliance check
4. Continue with the workflow

### If git worktree already exists:
1. Navigate to existing worktree
2. Pull latest changes from branch if it exists on remote
3. Continue with implementation

---

## Architectural Rules (MANDATORY)

These rules MUST be followed during all implementation:

### 1. Service Layer for Data Access
- **NEVER** query the database directly from Livewire components
- **NEVER** use `Model::query()`, `Model::where()`, etc. in controllers or Livewire components
- **ALL** database queries must be encapsulated in service classes
- Livewire components should only handle UI concerns and delegate to services

### 2. Constructor Dependency Injection
- **NEVER** use `app()` helper to resolve dependencies inside methods
- **NEVER** use `new ServiceClass()` to instantiate services inside methods
- **ALWAYS** inject dependencies through the constructor using property promotion
- Exceptions: Factory/seeder classes, Artisan command `handle()` methods, test classes

```php
// BAD - Never do this:
public function getReports(): Collection
{
    return DamageReport::where('user_id', auth()->id())->get();  // Direct query - VIOLATION
}

public function submit(int $id): void
{
    $service = app(ReportService::class);  // Service locator - VIOLATION
    $service->submit($id);
}

// GOOD - Always do this:
public function __construct(private ReportService $reportService) {}

public function getReports(): Collection
{
    return $this->reportService->getForCurrentUser();  // Delegated to service
}

public function submit(int $id): void
{
    $this->reportService->submit($id);  // Injected dependency
}
```

---

## Notes

- All work happens in the git worktree, keeping the main working directory clean
- Each implementation phase gets its own commit for clear history
- Quality checks happen AFTER all implementation to avoid redundant reviews
- The PR is created against the main branch automatically
- Run agents in parallel where dependencies allow (e.g., backend + frontend can sometimes run together if they don't depend on each other)
- All implementations must follow the Architectural Rules above

---

Now begin the development process for: **$ARGUMENTS**
