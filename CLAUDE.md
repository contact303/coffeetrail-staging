# CoffeeTrail — MyListing Child Theme

WordPress child theme for the MyListing theme. Site domain: coffee carts and
food trucks ("CoffeeTrail"). All user-facing copy is Hebrew, RTL.

## Where the work is

Almost all custom logic lives in a single self-contained module:

```
includes/ct-flow/
```

**Read `includes/ct-flow/CLAUDE.md` before doing anything in this repo.**
It is the authoritative map of the registration / listing-creation system:
architecture, step order, state model, meta-key table, and known defects.
This root file only routes you there — it deliberately does not duplicate it.

The module is bootstrapped from `functions.php` (a single `require_once` of
`includes/ct-flow/ct-flow.php`). `functions.php` is also where several
`add_action` / `add_filter` wirings for the module are expected to live, so
it must stay in scope for any investigation of what is or is not hooked up.

## Documentation status

| File | Status |
|---|---|
| `includes/ct-flow/CLAUDE.md` | Current. Authoritative. |
| `includes/ct-flow/CT-FLOW-DETAILED-SPEC.md` | **Largely outdated.** Describes a previous implementation that was replaced by the wizard. Some sections (Grow config, constants, selective approval) are still accurate. Do not treat it as truth — cross-check against code, and see the discrepancy table in `includes/ct-flow/CLAUDE.md`. |

## Ownership context

This module was written by a previous developer who has left the project.
Nobody currently on the project authored it. Two consequences:

1. **Code comments and docblocks are evidence of intent, not of behaviour.**
   Several comments confidently describe fixes that do not appear to work.
   If a comment claims something the surrounding code does not do, flag it
   rather than trusting it.
2. **There is no test suite and no staging parity guarantee.** Anything that
   cannot be settled by static reading needs an explicit runtime check, and
   you should say so instead of inferring.

## Working rules

- Default to plan mode for anything touching `includes/ct-flow/`.
- Never edit the MyListing parent theme. Overrides go in the child theme.
- Keep the existing comment style: full docblocks, `// ===` section banners,
  comments in English, explaining *why* and not only *what*.
- All strings shown to users must be Hebrew and RTL-safe.
- Distinguish clearly in any report between (a) defects inherited from the
  previous developer and (b) work for the feature currently being built.
  These are billed separately.
