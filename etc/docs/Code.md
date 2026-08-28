[<< Go Back](Readme.md)

## Index
- [Code Standards](#code-standards)
    - [Naming Conventions](#naming-conventions)
    - [File Structure](#file-structure)
    - [Static Analysis](#static-analysis)
- [Controllers](Controllers.md)
- [Repositories](Repositories.md)

## Code Standards



### Naming Conventions
- **Classes**: `PascalCase`
- **Methods**: `camelCase`
- **Variables**: `camelCase`
- **Constants**: `UPPER_CASE`

### File Structure
- One class per file.
- Filename should match the class name.
- Files should be named in `PascalCase`.

### Static Analysis

`src/` is analysed with [PHPStan](https://phpstan.org/) at **level 6**, configured in `phpstan.dist.neon`. Run it with `make phpstan/run` (also wired into `make linters/run`). `tests/` is intentionally out of scope.

Pre-existing debt (mostly missing `array<...>` generics — `src/` had exactly one such annotation when PHPStan was introduced) is frozen in `phpstan-baseline.neon`, generated with `make phpstan/baseline`.

Rules:
- **Never add a new entry to the baseline to silence an error your change introduced.** Fix the error instead — type the array properly, or add the missing annotation. The baseline exists to grandfather in old debt, not to launder new debt.
- Regenerating the baseline (`make phpstan/baseline`) is only for: raising the PHPStan level, or shrinking the file after fixing debt by hand in files you're not otherwise touching. Both should be their own commit, separate from feature changes.
- When you touch a file that has baseline entries, fixing them while you're in there is welcome but not required.
