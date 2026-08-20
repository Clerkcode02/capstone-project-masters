# Claude Code Setup Kit — AI-Driven Work Operating System

## Install

Copy these into the root of your (empty or fresh) project directory:

```
your-project/
├── CLAUDE.md
├── MCP-SETUP.md          # reference only, not read by Claude Code
├── PROMPTS.md            # reference only, not read by Claude Code
└── .claude/
    └── skills/
        ├── capacity-engine/SKILL.md
        ├── database-schema/SKILL.md
        ├── laravel-conventions/SKILL.md
        ├── livewire-dashboard/SKILL.md
        └── capstone-artifacts/SKILL.md
```

Verify Claude Code sees the skills:

```bash
ls .claude/skills/*/SKILL.md
```

Then start with Prompt 0.1 in PROMPTS.md.

## What each piece does

| File | Loaded | Purpose |
|---|---|---|
| `CLAUDE.md` | every session | project memory: rules, stack, vocabulary, module map |
| `capacity-engine` | on demand | the formulas — the research contribution |
| `database-schema` | on demand | exact schema so migrations don't drift |
| `laravel-conventions` | on demand | where code goes, security checklist |
| `livewire-dashboard` | on demand | UI and tier colour rules |
| `capstone-artifacts` | on demand | UAT scripts, ISO 25010 instrument, user guide |

Skills load only when relevant, so all five together cost ~500 tokens per session
until one is actually needed.
